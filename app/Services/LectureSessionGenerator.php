<?php

namespace App\Services;

use App\Models\ExtraLectureRequest;
use App\Models\LectureSession;
use App\Models\Timetable;
use Carbon\CarbonInterface;

class LectureSessionGenerator
{
    public function generateForDate(CarbonInterface $date): void
    {
        $dayOfWeek = (int) $date->format('N');

        Timetable::query()
            ->where('status', 'active')
            ->where('day_of_week', $dayOfWeek)
            ->get()
            ->each(function (Timetable $timetable) use ($date) {
                LectureSession::firstOrCreate(
                    [
                        'timetable_id' => $timetable->id,
                        'lecture_date' => $date->toDateString(),
                    ],
                    [
                        'subject_assignment_id' => $timetable->subject_assignment_id,
                        'start_time' => $timetable->start_time,
                        'end_time' => $timetable->end_time,
                        'lecture_no' => $timetable->lecture_no,
                        'session_type' => $timetable->slot_type ?? 'regular',
                        'status' => 'scheduled',
                    ]
                );
            });

        ExtraLectureRequest::query()
            ->where('approval_status', 'approved')
            ->whereDate('requested_date', $date)
            ->get()
            ->each(function (ExtraLectureRequest $request) {
                LectureSession::firstOrCreate(
                    [
                        'extra_lecture_request_id' => $request->id,
                    ],
                    [
                        'subject_assignment_id' => $request->subject_assignment_id,
                        'lecture_date' => $request->requested_date,
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time,
                        'session_type' => $request->session_type,
                        'status' => 'scheduled',
                    ]
                );
            });
    }
}
