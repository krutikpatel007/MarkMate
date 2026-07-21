<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Student;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SemesterPromotionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || !$user->isAdmin()) {
                abort(403, 'Unauthorized access to student promotion wizard.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Load all active class sections with count of students
        $classSections = ClassSection::with(['program', 'semester'])
            ->withCount(['students' => function($q) {
                $q->where('status', 'active');
            }])
            ->get()
            ->sortBy(fn($c) => $c->program->program_code . ' Sem ' . $c->semester->semester_no . ' ' . $c->section_name);

        return view('admin.promotion.index', [
            'classSections' => $classSections,
        ]);
    }

    public function promote(Request $request)
    {
        $validated = $request->validate([
            'source_class_section_id' => ['required', 'exists:class_sections,id'],
            'action_type' => ['required', 'in:promote,graduate'],
            'target_class_section_id' => ['required_if:action_type,promote', 'nullable', 'exists:class_sections,id'],
        ]);

        $sourceClass = ClassSection::with(['program', 'semester'])->findOrFail($validated['source_class_section_id']);
        $studentCount = Student::where('class_section_id', $sourceClass->id)->where('status', 'active')->count();

        if ($studentCount === 0) {
            return redirect()->route('admin.promotion.index')->with('error', 'There are no active students in ' . $sourceClass->display_name . ' to promote.');
        }

        if ($validated['action_type'] === 'graduate') {
            // Graduate students
            DB::transaction(function () use ($sourceClass, $studentCount) {
                // Update student status to 'inactive' or 'graduated' (if status supports it, let's set status = 'graduated' or 'inactive')
                Student::where('class_section_id', $sourceClass->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'inactive' // Using 'inactive' as standard status matching DB setup
                    ]);

                // Audit Log
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'students_graduated',
                    'entity_type' => ClassSection::class,
                    'entity_id' => $sourceClass->id,
                    'ip_address' => request()->ip(),
                    'old_values' => ['class_section_id' => $sourceClass->id, 'status' => 'active'],
                    'new_values' => ['status' => 'inactive', 'student_count' => $studentCount]
                ]);
            });

            return redirect()->route('admin.promotion.index')->with('success', 'Successfully graduated ' . $studentCount . ' students from ' . $sourceClass->display_name . '.');
        }

        // Action is 'promote'
        $targetClass = ClassSection::with(['program', 'semester'])->findOrFail($validated['target_class_section_id']);

        if ($sourceClass->id === $targetClass->id) {
            return redirect()->route('admin.promotion.index')->with('error', 'Source and target class sections cannot be the same.');
        }

        if ($sourceClass->program_id !== $targetClass->program_id) {
            return redirect()->route('admin.promotion.index')->with('error', 'Cannot promote between different academic programs (' . $sourceClass->program->program_code . ' to ' . $targetClass->program->program_code . ').');
        }

        DB::transaction(function () use ($sourceClass, $targetClass, $studentCount) {
            // Promote students
            Student::where('class_section_id', $sourceClass->id)
                ->where('status', 'active')
                ->update([
                    'class_section_id' => $targetClass->id,
                    'semester_id' => $targetClass->semester_id,
                ]);

            // Audit Log
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'students_promoted',
                'entity_type' => ClassSection::class,
                'entity_id' => $sourceClass->id,
                'ip_address' => request()->ip(),
                'old_values' => [
                    'class_section_id' => $sourceClass->id,
                    'semester_id' => $sourceClass->semester_id,
                    'student_count' => $studentCount
                ],
                'new_values' => [
                    'class_section_id' => $targetClass->id,
                    'semester_id' => $targetClass->semester_id,
                    'student_count' => $studentCount
                ]
            ]);
        });

        return redirect()->route('admin.promotion.index')->with('success', 'Successfully promoted ' . $studentCount . ' students from ' . $sourceClass->display_name . ' to ' . $targetClass->display_name . '.');
    }
}
