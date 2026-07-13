@extends('layouts.app')

@section('title', 'Mark Attendance | SCSA Attendance')
@section('page-title', 'Mark Attendance')
@section('page-subtitle')
    {{ $session->subjectAssignment->subject->subject_name }}
    | {{ $session->subjectAssignment->classSection->display_name }}
    | {{ $session->lecture_date->format('d M Y') }}
@endsection

@section('content')
    <div class="card">
        <div class="grid grid-4" style="margin-bottom: 1.125rem;">
            <div>
                <div class="muted">Faculty</div>
                <strong>{{ $session->subjectAssignment->faculty->user->name }}</strong>
            </div>
            <div>
                <div class="muted">Time</div>
                <strong>{{ substr($session->start_time, 0, 5) }} - {{ substr($session->end_time, 0, 5) }}</strong>
            </div>
            <div>
                <div class="muted">Session Type</div>
                <strong>{{ $session->session_type === 'lab' ? 'Lab' : 'Lecture' }}</strong>
            </div>
            <div>
                <div class="muted">Status</div>
                <span class="badge">{{ $session->status }}</span>
            </div>
        </div>

        @if($canMarkAttendance)
            <form method="post" action="{{ route('attendance.store', $session) }}">
                @csrf
                <div class="actions" style="margin-bottom: 1rem; justify-content: flex-end;">
                    <button type="button" class="button secondary" id="mark-all-present" style="min-height: unset; padding: 0.45rem 1rem; font-size: 0.8125rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem; border-color: var(--color-scsa-accent); color: var(--color-scsa-accent-deep);">
                        <span>⚡ Quick-Mark All Present</span>
                    </button>
                </div>
                <table>
                    <thead>
                    <tr>
                        <th>Roll No.</th>
                        <th>Enrollment</th>
                        <th>Student</th>
                        <th>Attendance</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $student)
                        @php($currentStatus = old("attendance.{$student->id}", $records->get($student->id)?->status ?? ($activeLeaves->has($student->id) ? 'absent_with_leave' : 'absent')))
                        <tr>
                            <td>{{ $student->roll_no }}</td>
                            <td>{{ $student->enrollment_no }}</td>
                            <td>
                                {{ $student->user->name }}
                                @if($activeLeaves->has($student->id))
                                    <span class="badge warning" style="margin-left: 0.5rem; font-size: 0.6875rem;">On Approved Leave</span>
                                @endif
                            </td>
                            <td>
                                <div class="quick-pills">
                                    <label class="pill-btn present {{ $currentStatus === 'present' ? 'active' : '' }}" onclick="selectPill(this, 'present')">
                                        <input type="radio" class="pill-radio" name="attendance[{{ $student->id }}]" value="present" @checked($currentStatus === 'present')>
                                        Present
                                    </label>
                                    <label class="pill-btn absent {{ $currentStatus === 'absent' ? 'active' : '' }}" onclick="selectPill(this, 'absent')">
                                        <input type="radio" class="pill-radio" name="attendance[{{ $student->id }}]" value="absent" @checked($currentStatus === 'absent')>
                                        Absent
                                    </label>
                                    <label class="pill-btn absent_with_leave {{ $currentStatus === 'absent_with_leave' ? 'active' : '' }}" onclick="selectPill(this, 'absent_with_leave')">
                                        <input type="radio" class="pill-radio" name="attendance[{{ $student->id }}]" value="absent_with_leave" @checked($currentStatus === 'absent_with_leave')>
                                        Leave
                                    </label>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="actions" style="margin-top: 1rem;">
                    <button class="button" type="submit">Save Attendance</button>
                    <a class="button secondary" href="{{ route('dashboard') }}">Back</a>
                </div>
            </form>
        @else
            <div class="alert">This attendance session is read-only.</div>
            <table>
                <thead>
                <tr>
                    <th>Roll No.</th>
                    <th>Enrollment</th>
                    <th>Student</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($students as $student)
                    <tr>
                        <td>{{ $student->roll_no }}</td>
                        <td>{{ $student->enrollment_no }}</td>
                        <td>
                            {{ $student->user->name }}
                            @if($activeLeaves->has($student->id))
                                <span class="badge warning" style="margin-left: 0.5rem; font-size: 0.6875rem;">On Approved Leave</span>
                            @endif
                        </td>
                        <td><span class="badge">{{ str_replace('_', ' ', $records->get($student->id)?->status ?? 'not marked') }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($canRequestCorrection)
                @if($pendingCorrection)
                    <div class="alert warning" style="margin-top: 1rem;">
                        A correction request is already pending for this session.
                    </div>
                @else
                    <form method="post" action="{{ route('attendance.correction-requests.store', $session) }}" style="margin-top: 1rem;">
                        @csrf

                        <h2>Request Attendance Correction</h2>
                        <div class="field">
                            <label for="reason">Reason</label>
                            <textarea id="reason" name="reason" required placeholder="Explain why this locked attendance needs correction.">{{ old('reason') }}</textarea>
                        </div>

                        <table>
                            <thead>
                            <tr>
                                <th>Roll No.</th>
                                <th>Enrollment</th>
                                <th>Student</th>
                                <th>Requested Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($students as $student)
                                @php($currentStatus = old("attendance.{$student->id}", $records->get($student->id)?->status ?? 'absent'))
                                <tr>
                                    <td>{{ $student->roll_no }}</td>
                                    <td>{{ $student->enrollment_no }}</td>
                                    <td>
                                        {{ $student->user->name }}
                                        @if($activeLeaves->has($student->id))
                                            <span class="badge warning" style="margin-left: 0.5rem; font-size: 0.6875rem;">On Approved Leave</span>
                                        @endif
                                    </td>
                                    <td>
                                        <select name="attendance[{{ $student->id }}]" required>
                                            <option value="present" @selected($currentStatus === 'present')>Present</option>
                                            <option value="absent" @selected($currentStatus === 'absent')>Absent</option>
                                            <option value="absent_with_leave" @selected($currentStatus === 'absent_with_leave')>Absent with Leave</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div class="actions" style="margin-top: 1rem;">
                            <button class="button" type="submit">Send Correction Request</button>
                            <a class="button secondary" href="{{ route('attendance-corrections.index') }}">View Requests</a>
                        </div>
                    </form>
                @endif
            @endif
        @endif
    </div>

    <!-- Segments Control Script -->
    <script>
        function selectPill(label, value) {
            const container = label.closest('.quick-pills');
            if (!container) return;
            const buttons = container.querySelectorAll('.pill-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            label.classList.add('active');
            const radio = label.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const markAllPresentBtn = document.getElementById('mark-all-present');
            if (markAllPresentBtn) {
                markAllPresentBtn.addEventListener('click', function() {
                    const containers = document.querySelectorAll('.quick-pills');
                    containers.forEach(container => {
                        // In university lectures, we don't overwrite approved leaves automatically to "Present"
                        // to avoid overrides of pre-approved leaves, unless the professor wants to.
                        // However, a simple override check is:
                        const hasLeave = container.querySelector('.pill-btn.absent_with_leave.active');
                        if (hasLeave) return; // Skip students who are already marked as leave
                        
                        const presentBtn = container.querySelector('.pill-btn.present');
                        if (presentBtn) {
                            selectPill(presentBtn, 'present');
                        }
                    });
                });
            }
        });
    </script>
@endsection
