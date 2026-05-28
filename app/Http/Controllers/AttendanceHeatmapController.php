<?php

namespace App\Http\Controllers;

use App\Models\LectureSession;
use App\Models\Department;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceHeatmapController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);

        $user = Auth::user();
        $isHod = $user->isHod();
        $manageableDeptIds = $isHod ? [$user->facultyProfile->department_id] : Department::pluck('id')->toArray();

        // 1. Fetch statistics of sessions for the last 30 days
        $stats = LectureSession::query()
            ->select([
                'lecture_date',
                DB::raw("sum(case when status in ('conducted', 'locked') then 1 else 0 end) as submitted_count"),
                DB::raw("count(*) as total_count")
            ])
            ->whereIn('status', ['scheduled', 'pending', 'conducted', 'locked'])
            ->whereBetween('lecture_date', [now()->subDays(29)->toDateString(), now()->toDateString()])
            ->when($isHod, function ($q) use ($manageableDeptIds) {
                $q->whereHas('subjectAssignment.classSection.program', function ($sub) use ($manageableDeptIds) {
                    $sub->whereIn('department_id', $manageableDeptIds);
                });
            })
            ->groupBy('lecture_date')
            ->get()
            ->keyBy(fn($row) => $row->lecture_date->toDateString());

        // 2. Generate exactly 30 days of calendar grid cells
        $heatmapData = [];
        for ($i = 29; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dateStr = $dateObj->toDateString();
            $dayOfWeek = $dateObj->format('l');

            $dayStats = $stats->get($dateStr);

            if ($dayStats) {
                $rate = $dayStats->total_count > 0
                    ? round(($dayStats->submitted_count / $dayStats->total_count) * 100, 1)
                    : 100;
                
                // Color mapping
                if ($rate >= 100.0) {
                    $colorClass = 'emerald';
                } elseif ($rate >= 75.0) {
                    $colorClass = 'mint';
                } elseif ($rate >= 50.0) {
                    $colorClass = 'amber';
                } else {
                    $colorClass = 'crimson';
                }

                $heatmapData[] = [
                    'date' => $dateStr,
                    'display_date' => $dateObj->format('d M Y'),
                    'day_of_week' => $dayOfWeek,
                    'submitted' => $dayStats->submitted_count,
                    'total' => $dayStats->total_count,
                    'rate' => $rate,
                    'color_class' => $colorClass,
                    'has_lectures' => true,
                ];
            } else {
                $heatmapData[] = [
                    'date' => $dateStr,
                    'display_date' => $dateObj->format('d M Y'),
                    'day_of_week' => $dayOfWeek,
                    'submitted' => 0,
                    'total' => 0,
                    'rate' => 0,
                    'color_class' => 'empty',
                    'has_lectures' => false,
                ];
            }
        }

        return view('attendance.heatmap', [
            'heatmapData' => $heatmapData,
        ]);
    }

    public function showDayDetails($date): View
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);

        $user = Auth::user();
        $isHod = $user->isHod();
        $manageableDeptIds = $isHod ? [$user->facultyProfile->department_id] : Department::pluck('id')->toArray();

        $sessions = LectureSession::with([
            'subjectAssignment.subject',
            'subjectAssignment.classSection',
            'subjectAssignment.faculty.user'
        ])
        ->whereDate('lecture_date', $date)
        ->whereIn('status', ['scheduled', 'pending', 'conducted', 'locked'])
        ->when($isHod, function ($q) use ($manageableDeptIds) {
            $q->whereHas('subjectAssignment.classSection.program', function ($sub) use ($manageableDeptIds) {
                $sub->whereIn('department_id', $manageableDeptIds);
            });
        })
        ->orderBy('start_time')
        ->orderBy('lecture_no')
        ->get();

        $formattedDate = Carbon::parse($date)->format('d F Y');

        return view('attendance.heatmap_details', [
            'date' => $date,
            'formattedDate' => $formattedDate,
            'sessions' => $sessions,
        ]);
    }
}
