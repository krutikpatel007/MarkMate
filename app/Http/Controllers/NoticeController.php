<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\Department;
use App\Models\ClassSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_if($user->isStudent(), 403, 'Students are not allowed to access notice management.');
        
        $notices = Notice::with(['author'])
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $query->where('author_id', $user->id);
            })
            ->latest()
            ->get();
            
        // Prepare options for audience based on role
        $departments = collect();
        $classes = collect();
        
        if ($user->isAdmin() || $user->isCoe() || $user->isAdminStaff()) {
            $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        } elseif ($user->isHod()) {
            $manageableIds = $this->manageableDepartmentIds();
            $departments = Department::whereIn('id', $manageableIds)->where('status', 'active')->orderBy('department_name')->get();
        } elseif ($user->isFaculty()) {
            $faculty = $user->facultyProfile;
            $classes = ClassSection::whereHas('subjectAssignments', function($q) use ($faculty) {
                $q->where('faculty_id', $faculty->id);
            })->orderBy('display_name')->get();
        }

        return view('notices.index', [
            'notices' => $notices,
            'departments' => $departments,
            'classes' => $classes,
        ]);
    }
    
    // helper for hod
    protected function manageableDepartmentIds(): array
    {
        $user = Auth::user();
        if ($user->isAdmin() || $user->isCoe() || $user->isAdminStaff()) {
            return \App\Models\Department::pluck('id')->toArray();
        }
        if ($user->isHod()) {
            $userDeptCode = $user->facultyProfile?->department?->department_code;
            if ($userDeptCode === 'EXAM') {
                return \App\Models\Department::pluck('id')->toArray();
            }
            return [$user->facultyProfile->department_id];
        }
        return [];
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isStudent(), 403, 'Students are not allowed to post notices.');
        
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['info', 'warning', 'danger', 'success'])],
            'audience_type' => ['required', Rule::in(['global', 'department', 'department_faculty', 'department_students', 'class'])],
            'audience_id' => ['nullable', 'integer'],
        ]);
        
        // Authorization checks for audience
        if ($validated['audience_type'] === 'global') {
            abort_unless($user->isAdmin() || $user->isFeesDept(), 403, 'Only Admins and Fees Department can post global notices.');
            $validated['audience_id'] = null;
        } elseif (in_array($validated['audience_type'], ['department', 'department_faculty', 'department_students'], true)) {
            abort_unless($user->isAdmin() || $user->isHod() || $user->isCoe() || $user->isAdminStaff(), 403, 'Only Admins, HODs, COEs and Admin Staff can post department notices.');
            if ($user->isHod()) {
                abort_unless((int)$validated['audience_id'] === $user->facultyProfile->department_id, 403, 'You can only post to your own department.');
            }
        } elseif ($validated['audience_type'] === 'class') {
            abort_unless($user->isFaculty(), 403, 'Only Faculty can post class-specific notices.');
            // Verify faculty teaches this class
            $teachesClass = \App\Models\SubjectAssignment::where('faculty_id', $user->facultyProfile->id)
                ->where('class_section_id', $validated['audience_id'])
                ->exists();
            abort_unless($teachesClass, 403, 'You can only post notices to classes you teach.');
        }

        Notice::create([
            'author_id' => $user->id,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'],
            'audience_type' => $validated['audience_type'],
            'audience_id' => $validated['audience_id'] ?? null,
        ]);

        return redirect()->route('notices.index')->with('status', 'Notice posted successfully.');
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $user = Auth::user();
        abort_if($user->isStudent(), 403, 'Students are not allowed to delete notices.');
        
        // Only author or Admin can delete
        abort_unless($user->isAdmin() || $notice->author_id === $user->id, 403, 'Unauthorized to delete this notice.');
        
        $notice->delete();
        
        return redirect()->route('notices.index')->with('status', 'Notice removed successfully.');
    }
}
