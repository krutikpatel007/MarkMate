@if(isset($isExamDept) && $isExamDept)
    <div class="grid grid-4" style="animation: fadeIn 0.3s ease-in-out;">
        <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-accent); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Total Assigned Courses</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-accent); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['total_courses'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Active academic courses</div>
        </div>

        <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-gold); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Draft Mode (Faculty)</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-gold); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 17.59a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['draft_count'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Marks entry in progress</div>
        </div>

        <div class="card stat-card" style="border-left: 4px solid #3b82f6; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">HOD Review Pending</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #3b82f6; opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['hod_review_count'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Submitted to academic HODs</div>
        </div>

        <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-success); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Finalized (Exam Dept)</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-success); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['submitted_to_exam_count'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Locked & sent to Exam Controller</div>
        </div>
    </div>
@else
    <div class="grid grid-4" style="animation: fadeIn 0.3s ease-in-out;">
        <a class="card stat-card" href="{{ route('attendance.monitor') }}" style="text-decoration: none;"><div class="muted">Total Lectures Today</div><div class="stat">{{ $stats['sessions_today'] }}</div></a>
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'conducted']) }}" style="text-decoration: none;"><div class="muted">Submitted</div><div class="stat">{{ $stats['submitted_today'] }}</div></a>
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'pending']) }}" style="text-decoration: none;"><div class="muted">Pending</div><div class="stat">{{ $stats['pending_sessions'] }}</div></a>
        <a class="card stat-card" href="{{ route('extra-lectures.index') }}" style="text-decoration: none;"><div class="muted">Pending Extra Requests</div><div class="stat">{{ $stats['pending_extra_requests'] }}</div></a>
        <a class="card stat-card" href="{{ route('reports.index') }}#low-attendance-classes" style="text-decoration: none;"><div class="muted">Classes Below 75%</div><div class="stat">{{ $stats['low_attendance_classes'] }}</div></a>
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status_group' => 'pending']) }}" style="text-decoration: none;"><div class="muted">Faculty Pending Submission</div><div class="stat">{{ $stats['faculty_pending'] }}</div></a>
        <a class="card stat-card" href="{{ route('reports.index') }}#defaulters" style="text-decoration: none;"><div class="muted">Defaulters Below 75%</div><div class="stat">{{ $stats['defaulters'] }}</div></a>
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'cancelled']) }}" style="text-decoration: none;"><div class="muted">Cancelled Today</div><div class="stat">{{ $stats['cancelled_today'] }}</div></a>
    </div>
@endif

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
