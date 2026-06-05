@extends('layouts.app')

@section('title', 'Internal Marks Recheck | Shreyarth University')
@section('page-title', 'Marks Re-Evaluation')
@section('page-subtitle', 'Apply for recounting or paper rechecking on finalized internal marks')

@section('content')
<div class="grid grid-3">
    <section class="card" style="grid-column: span 2;">
        <h2>Your Finalized Marks Sheets</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Mid-Sem (20)</th>
                    <th>CIE (30)</th>
                    <th>Total (50)</th>
                    <th>Recheck Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($marks as $mark)
                    @php($req = $requests->get($mark->subject_assignment_id))
                    <tr>
                        <td><strong>{{ $mark->subjectAssignment->subject->subject_code }}</strong></td>
                        <td>{{ $mark->subjectAssignment->subject->subject_name }}</td>
                        <td>{{ $mark->mid_sem_20 }}</td>
                        <td>{{ $mark->cie_30 }}</td>
                        <td>
                            <span class="badge {{ $mark->total_50 < 20 ? 'danger' : 'success' }}">{{ $mark->total_50 }}</span>
                        </td>
                        <td>
                            @if($req)
                                @if($req->status === 'requested')
                                    <span class="badge" style="background: var(--color-scsa-line); color: var(--color-scsa-gold);">Requested</span>
                                @elseif($req->status === 'assigned')
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">Assigned</span>
                                @elseif($req->status === 'scrutinized')
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">Scrutinized</span>
                                @elseif($req->status === 'completed')
                                    <span class="badge success">Completed</span>
                                @endif
                            @else
                                <span class="muted">No Request</span>
                            @endif
                        </td>
                        <td>
                            @if($req)
                                <button class="button secondary" disabled>Applied</button>
                            @else
                                <button type="button" class="button" onclick="openApplyModal({{ $mark->subject_assignment_id }}, '{{ addslashes($mark->subjectAssignment->subject->subject_name) }}', '{{ $mark->total_50 }}')">Apply Recheck</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted" style="text-align: center; padding: 2.5rem 0;">
                            No finalized internal marks sheets found. Rechecks are only available after HOD submits scores to Exam Department.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Active Scrutiny Pipeline</h2>
        <p class="muted" style="font-size: 0.8125rem; line-height: 1.5; margin-bottom: 1.25rem;">
            When you apply for a recheck, the Exam Coordinator delegates the grading audit to a separate, senior faculty member who reviews calculations.
        </p>

        @forelse($requests->values() as $req)
            <div class="list-divider" style="padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem;">
                    <strong>{{ $req->subjectAssignment->subject->subject_name }}</strong>
                    <span class="badge secondary" style="font-size: 0.6875rem; text-transform: uppercase;">{{ $req->type }}</span>
                </div>
                <div class="muted" style="font-size: 0.75rem; display: flex; justify-content: space-between; margin-top: 0.25rem;">
                    <span>Original Marks: {{ $req->original_marks }}</span>
                    @if($req->status === 'completed')
                        <span style="font-weight: 700; color: var(--color-scsa-success);">New Marks: {{ $req->revised_marks ?? $req->original_marks }}</span>
                    @else
                        <span class="muted">Status: {{ ucwords($req->status) }}</span>
                    @endif
                </div>
                @if($req->coordinator_remarks)
                    <div style="font-size: 0.75rem; margin-top: 0.45rem; padding: 0.4rem 0.6rem; background: var(--color-scsa-line); border-left: 3px solid var(--color-scsa-gold); border-radius: 4px; color: var(--color-scsa-ink);">
                        📝 <strong>Feedback:</strong> {{ $req->coordinator_remarks }}
                    </div>
                @endif
            </div>
        @empty
            <p class="muted" style="text-align: center; padding: 2rem 0;">You have no active re-evaluation requests in progress.</p>
        @endforelse
    </section>
</div>

<!-- Apply Modal -->
<div id="apply-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 28rem; padding: 1.5rem; position: relative; border-radius: var(--border-radius-lg);">
        <h3 style="margin-bottom: 0.5rem;">Apply for Scrutiny / Recheck</h3>
        <p class="muted" style="font-size: 0.875rem; margin-bottom: 1.25rem;">
            Course: <strong id="modal-subject-name"></strong><br>
            Current Final Score: <strong id="modal-current-score"></strong> / 50
        </p>
        <form id="apply-form" method="post" action="">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label for="type" style="font-weight: 600; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem;">Application Type</label>
                <select name="type" id="type" style="width: 100%;" required>
                    <option value="recount">Recounting (Verifies mathematical summation accuracy)</option>
                    <option value="rechecking">Full Re-checking (Verifies grading completeness &amp; recount)</option>
                </select>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label for="student_remarks" style="font-weight: 600; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem;">Student Remarks / Discrepancy Reasons</label>
                <textarea name="student_remarks" id="student_remarks" rows="3" placeholder="Briefly explain the discrepancy (e.g. counting mistake in mid-sem, uncoded answers...)" style="width: 100%; font-family: inherit; border-radius: 4px; border: 1px solid var(--color-scsa-line);"></textarea>
            </div>
            <div class="actions" style="justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="button secondary" onclick="closeApplyModal()">Cancel</button>
                <button type="submit" class="button">Submit Recheck Application</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyModal(assignmentId, subjectName, currentScore) {
    const modal = document.getElementById('apply-modal');
    document.getElementById('modal-subject-name').innerText = subjectName;
    document.getElementById('modal-current-score').innerText = currentScore;
    
    const form = document.getElementById('apply-form');
    form.action = `/my-re-evaluations/${assignmentId}/apply`;
    modal.style.display = 'flex';
}

function closeApplyModal() {
    document.getElementById('apply-modal').style.display = 'none';
}
</script>
@endsection
