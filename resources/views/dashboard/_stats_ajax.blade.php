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
        <!-- Total Lectures -->
        <a class="card stat-card" href="{{ route('attendance.monitor') }}" style="text-decoration: none; border-left: 4px solid var(--color-scsa-accent); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Total Lectures Today</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-accent); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['sessions_today'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Scheduled sessions</div>
        </a>

        <!-- Submitted -->
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'conducted']) }}" style="text-decoration: none; border-left: 4px solid var(--color-scsa-success); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Submitted</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-success); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['submitted_today'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Attendance locked</div>
        </a>

        <!-- Pending -->
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'pending']) }}" style="text-decoration: none; border-left: 4px solid var(--color-scsa-danger); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Pending Sessions</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-danger); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['pending_sessions'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Awaiting submission</div>
        </a>

        <!-- Extra Requests -->
        <a class="card stat-card" href="{{ route('extra-lectures.index') }}" style="text-decoration: none; border-left: 4px solid #3b82f6; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Extra Requests</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #3b82f6; opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['pending_extra_requests'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Awaiting HOD approval</div>
        </a>

        <!-- Classes Below 75% -->
        <a class="card stat-card" href="{{ route('reports.index') }}#low-attendance-classes" style="text-decoration: none; border-left: 4px solid var(--color-scsa-danger); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Classes Below 75%</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-danger); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['low_attendance_classes'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Low attendance sections</div>
        </a>

        <!-- Faculty Pending -->
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status_group' => 'pending']) }}" style="text-decoration: none; border-left: 4px solid var(--color-scsa-gold); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Faculty Pending</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-gold); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['faculty_pending'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Staff with incomplete logs</div>
        </a>

        <!-- Defaulters -->
        <a class="card stat-card" href="{{ route('reports.index') }}#defaulters" style="text-decoration: none; border-left: 4px solid var(--color-scsa-danger); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Defaulters Below 75%</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-danger); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['defaulters'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Total student defaulters</div>
        </a>

        <!-- Exam Fee Clearance Widget -->
        <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-gold); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Exam Fee Clearance</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-gold); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['fee_clearance_rate'] }}%</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $stats['cleared_fees_count'] }} of {{ $stats['students_with_fees'] }} paid</div>
        </div>

        <!-- Cancelled Today -->
        <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'cancelled']) }}" style="text-decoration: none; border-left: 4px solid var(--color-scsa-muted); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Cancelled Today</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-muted); opacity: 0.8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat">{{ $stats['cancelled_today'] }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Cancelled slots</div>
        </a>
    </div>
@endif

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
