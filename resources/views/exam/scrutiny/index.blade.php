@extends('layouts.app')

@section('title', 'Marks Scrutiny Workspace | Shreyarth University')
@section('page-title', 'Marks Scrutiny')
@section('page-subtitle', 'Delegate recheck assignments to impartial reviewers and authorize finalized grade cards adjustments')

@section('content')
<div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 100%);">
    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
        <div style="flex: 1;">
            <h3 style="margin-bottom: 0.35rem; color: var(--color-scsa-accent);">Impartiality Control Active</h3>
            <p class="muted" style="font-size: 0.8125rem; line-height: 1.5; margin: 0;">
                The platform securely blocks recheck assignments from being allocated to the original class faculty. A senior evaluator must perform the recount.
            </p>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div style="text-align: center; padding: 0.5rem 1rem; background: var(--color-scsa-line); border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-scsa-ink);">{{ $requests->where('status', 'requested')->count() }}</div>
                <div class="muted" style="font-size: 0.6875rem; text-transform: uppercase;">Unassigned</div>
            </div>
            <div style="text-align: center; padding: 0.5rem 1rem; background: var(--color-scsa-line); border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: 800; color: #3b82f6;">{{ $requests->where('status', 'assigned')->count() }}</div>
                <div class="muted" style="font-size: 0.6875rem; text-transform: uppercase;">In Progress</div>
            </div>
            <div style="text-align: center; padding: 0.5rem 1rem; background: var(--color-scsa-line); border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: 800; color: #f59e0b;">{{ $requests->where('status', 'scrutinized')->count() }}</div>
                <div class="muted" style="font-size: 0.6875rem; text-transform: uppercase;">Scrutinized</div>
            </div>
        </div>
    </div>
</div>

<section class="card">
    <h2>Scrutiny Requests Pipeline</h2>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
            <tr>
                <th>Student Candidate</th>
                <th>Subject &amp; Code</th>
                <th>Type</th>
                <th>Grades</th>
                <th>Status</th>
                <th>Assigned Evaluator</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>
                        <strong>{{ $req->student->user->name }}</strong>
                        <div class="muted">{{ $req->student->enrollment_no }} | {{ $req->student->classSection->display_name }}</div>
                    </td>
                    <td>
                        <strong>{{ $req->subjectAssignment->subject->subject_name }}</strong>
                        <div class="muted">{{ $req->subjectAssignment->subject->subject_code }} (Orig: {{ $req->subjectAssignment->faculty->user->name }})</div>
                    </td>
                    <td>
                        <span class="badge secondary" style="text-transform: uppercase; font-size: 0.6875rem;">{{ $req->type }}</span>
                    </td>
                    <td>
                        <div style="font-weight: 500; font-size: 0.8125rem;">
                            Orig: <strong>{{ $req->original_marks }}</strong>
                        </div>
                        @if($req->revised_marks !== null)
                            <div style="color: #f59e0b; font-weight: 700; font-size: 0.8125rem;">
                                Rev: <strong>{{ $req->revised_marks }}</strong>
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'requested')
                            <span class="badge" style="background: var(--color-scsa-line); color: var(--color-scsa-gold);">Unassigned</span>
                        @elseif($req->status === 'assigned')
                            <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">Assigned</span>
                        @elseif($req->status === 'scrutinized')
                            <span class="badge animate-pulse" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">Scrutinized</span>
                        @elseif($req->status === 'completed')
                            <span class="badge success">Completed</span>
                        @endif
                    </td>
                    <td>
                        @if($req->evaluator)
                            <strong>{{ $req->evaluator->name }}</strong>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'requested')
                            <button type="button" class="button" onclick="openAssignModal({{ $req->id }}, '{{ addslashes($req->student->user->name) }}', '{{ addslashes($req->subjectAssignment->subject->subject_name) }}', {{ $req->subjectAssignment->faculty_id }})">Assign Evaluator</button>
                        @elseif($req->status === 'assigned')
                            <button class="button secondary" disabled>Pending Faculty Review</button>
                        @elseif($req->status === 'scrutinized')
                            <button type="button" class="button warning" onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->student->user->name) }}', '{{ addslashes($req->subjectAssignment->subject->subject_name) }}', '{{ $req->original_marks }}', '{{ $req->revised_marks }}', '{{ addslashes($req->evaluator_remarks) }}')">Review &amp; Approve</button>
                        @elseif($req->status === 'completed')
                            <button class="button secondary" disabled>Archived</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align: center; padding: 3rem 0;">
                        No student re-evaluation requests found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- Assignment Modal -->
<div id="assign-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 28rem; padding: 1.5rem; position: relative; border-radius: var(--border-radius-lg);">
        <h3 style="margin-bottom: 0.5rem;">Assign Scrutiny Reviewer</h3>
        <p class="muted" style="font-size: 0.875rem; margin-bottom: 1.25rem;">
            Student: <strong id="modal-student-name"></strong><br>
            Subject: <strong id="modal-subject-name"></strong>
        </p>
        <form id="assign-form" method="post" action="">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label for="assigned_to" style="font-weight: 600; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem;">Impartial Evaluator (Faculty)</label>
                <select name="assigned_to" id="assigned_to" style="width: 100%;" required>
                    <option value="">Select senior faculty member...</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->user->id }}" data-faculty-id="{{ $f->id }}">{{ $f->user->name }} ({{ $f->designation }})</option>
                    @endforeach
                </select>
            </div>
            <div class="actions" style="justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="button secondary" onclick="closeAssignModal()">Cancel</button>
                <button type="submit" class="button">Allocate Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Approval/Rejection Modal -->
<div id="approve-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 32rem; padding: 1.5rem; position: relative; border-radius: var(--border-radius-lg);">
        <h3 style="margin-bottom: 0.5rem; color: #f59e0b;">Finalize Re-Evaluation Audit</h3>
        <p class="muted" style="font-size: 0.875rem; margin-bottom: 1rem;">
            Student: <strong id="app-student-name"></strong><br>
            Course: <strong id="app-subject-name"></strong>
        </p>
        
        <div style="display: flex; gap: 1rem; padding: 0.75rem; background: var(--color-scsa-line); border-radius: 4px; margin-bottom: 1rem; justify-content: space-around; align-items: center;">
            <div style="text-align: center;">
                <span class="muted" style="font-size: 0.75rem;">Original Marks</span>
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-scsa-ink);" id="app-orig-marks"></div>
            </div>
            <div style="font-size: 1.5rem; color: var(--color-scsa-gold);">→</div>
            <div style="text-align: center;">
                <span class="muted" style="font-size: 0.75rem;">Evaluated Marks</span>
                <div style="font-size: 1.25rem; font-weight: 800; color: var(--color-scsa-success);" id="app-rev-marks"></div>
            </div>
        </div>

        <div style="margin-bottom: 1rem;">
            <span class="muted" style="font-size: 0.8125rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Evaluator Comments</span>
            <div style="font-size: 0.875rem; padding: 0.5rem; background: rgba(0,0,0,0.02); border-radius: 4px; line-height: 1.4; font-style: italic;" id="app-comments"></div>
        </div>

        <form id="decision-form" method="post" action="">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label for="coordinator_remarks" style="font-weight: 600; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem;">Coordinator Verification Remarks</label>
                <textarea name="coordinator_remarks" id="coordinator_remarks" rows="2" placeholder="e.g. Scrutiny marks approved. Auto-updated student gradebook..." style="width: 100%; font-family: inherit; border-radius: 4px; border: 1px solid var(--color-scsa-line);"></textarea>
            </div>
            <div class="actions" style="justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="button secondary" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" class="button danger" onclick="submitDecision('reject')">Reject Scrutiny</button>
                <button type="submit" class="button" onclick="submitDecision('approve')">Approve &amp; Update Marks</button>
            </div>
        </form>
    </div>
</div>

<script>
let activeOrigFacultyId = null;

function openAssignModal(requestId, studentName, subjectName, originalFacultyId) {
    const modal = document.getElementById('assign-modal');
    document.getElementById('modal-student-name').innerText = studentName;
    document.getElementById('modal-subject-name').innerText = subjectName;
    
    activeOrigFacultyId = originalFacultyId;
    
    // Impartiality client-side filter
    const select = document.getElementById('assigned_to');
    for (let i = 0; i < select.options.length; i++) {
        const option = select.options[i];
        const facultyId = parseInt(option.getAttribute('data-faculty-id'));
        if (facultyId === originalFacultyId) {
            option.disabled = true;
            option.text = option.text.split(' (Original Marker)')[0] + ' (Original Marker - BLOCKED)';
        } else {
            option.disabled = false;
        }
    }
    
    const form = document.getElementById('assign-form');
    form.action = `/exam/scrutiny/${requestId}/assign`;
    modal.style.display = 'flex';
}

function closeAssignModal() {
    document.getElementById('assign-modal').style.display = 'none';
}

function openApproveModal(requestId, studentName, subjectName, origMarks, revMarks, comments) {
    const modal = document.getElementById('approve-modal');
    document.getElementById('app-student-name').innerText = studentName;
    document.getElementById('app-subject-name').innerText = subjectName;
    document.getElementById('app-orig-marks').innerText = origMarks;
    document.getElementById('app-rev-marks').innerText = revMarks;
    document.getElementById('app-comments').innerText = comments;
    
    const form = document.getElementById('decision-form');
    form.setAttribute('data-request-id', requestId);
    modal.style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approve-modal').style.display = 'none';
}

function submitDecision(action) {
    const form = document.getElementById('decision-form');
    const requestId = form.getAttribute('data-request-id');
    
    if (action === 'approve') {
        form.action = `/exam/scrutiny/${requestId}/approve`;
    } else {
        form.action = `/exam/scrutiny/${requestId}/reject`;
    }
}
</script>
@endsection
