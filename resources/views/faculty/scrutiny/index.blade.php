@extends('layouts.app')

@section('title', 'Assigned Scrutinies | Shreyarth University')
@section('page-title', 'Paper Scrutiny')
@section('page-subtitle', 'Perform recount or paper rechecking audits assigned by the Examination Department')

@section('content')
<section class="card">
    <h2>Your Assigned Scrutiny Audits</h2>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
            <tr>
                <th>Student Candidate</th>
                <th>Subject Details</th>
                <th>Audit Type</th>
                <th>Original Marks</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>
                        <strong>{{ $req->student->user->name }}</strong>
                        <div class="muted">{{ $req->student->enrollment_no }} | Class: {{ $req->student->classSection->display_name }}</div>
                    </td>
                    <td>
                        <strong>{{ $req->subjectAssignment->subject->subject_name }}</strong>
                        <div class="muted">{{ $req->subjectAssignment->subject->subject_code }}</div>
                    </td>
                    <td>
                        <span class="badge secondary" style="text-transform: uppercase; font-size: 0.6875rem;">{{ $req->type }}</span>
                    </td>
                    <td>
                        <strong>{{ $req->original_marks }}</strong> / 50
                    </td>
                    <td>
                        @if($req->status === 'assigned')
                            <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">Pending Audit</span>
                        @elseif($req->status === 'scrutinized')
                            <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">Scrutinized</span>
                        @elseif($req->status === 'completed')
                            <span class="badge success">Approved &amp; Updated</span>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'assigned')
                            <button type="button" class="button" onclick="openScrutinizeModal({{ $req->id }}, '{{ addslashes($req->student->user->name) }}', '{{ addslashes($req->subjectAssignment->subject->subject_name) }}', '{{ $req->original_marks }}', '{{ $req->type }}')">Perform Audit</button>
                        @else
                            <button class="button secondary" disabled>Submitted</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted" style="text-align: center; padding: 3rem 0;">
                        You have no paper scrutiny audits assigned at this time.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- Scrutiny Audit Modal -->
<div id="scrutinize-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 30rem; padding: 1.5rem; position: relative; border-radius: var(--border-radius-lg);">
        <h3 style="margin-bottom: 0.5rem; color: var(--color-scsa-accent);">Perform Marks Scrutiny Audit</h3>
        <p class="muted" style="font-size: 0.875rem; margin-bottom: 1rem;">
            Student: <strong id="modal-student-name"></strong><br>
            Subject: <strong id="modal-subject-name"></strong>
        </p>

        <div style="padding: 0.5rem 0.75rem; background: var(--color-scsa-line); border-radius: 4px; font-size: 0.8125rem; font-weight: 500; margin-bottom: 1.25rem;">
            🔍 Application Mode: <span id="modal-audit-type" style="font-weight: 700; text-transform: uppercase; color: var(--color-scsa-gold);"></span><br>
            Original Marks Cache: <strong id="modal-orig-score"></strong> / 50
        </div>

        <form id="scrutinize-form" method="post" action="">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label for="revised_marks" style="font-weight: 600; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem;">Revised Total Marks (out of 50)</label>
                <input type="number" step="0.01" min="0" max="50" name="revised_marks" id="revised_marks" required style="width: 100%;">
                <small class="muted" style="font-size: 0.6875rem;">If the recount matches the original score exactly, please enter the original marks.</small>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label for="evaluator_remarks" style="font-weight: 600; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem;">Scrutinizer Audit Remarks</label>
                <textarea name="evaluator_remarks" id="evaluator_remarks" rows="3" required placeholder="Describe recounting or rechecking details (e.g. Recounted mid-sem and CIE, summation is correct...)" style="width: 100%; font-family: inherit; border-radius: 4px; border: 1px solid var(--color-scsa-line);"></textarea>
            </div>
            <div class="actions" style="justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="button secondary" onclick="closeScrutinizeModal()">Cancel</button>
                <button type="submit" class="button">Submit Audit Results</button>
            </div>
        </form>
    </div>
</div>

<script>
function openScrutinizeModal(requestId, studentName, subjectName, origScore, auditType) {
    const modal = document.getElementById('scrutinize-modal');
    document.getElementById('modal-student-name').innerText = studentName;
    document.getElementById('modal-subject-name').innerText = subjectName;
    document.getElementById('modal-orig-score').innerText = origScore;
    document.getElementById('modal-audit-type').innerText = auditType;
    
    // Set default value as original score
    document.getElementById('revised_marks').value = origScore;
    
    const form = document.getElementById('scrutinize-form');
    form.action = `/faculty/scrutiny/${requestId}/submit`;
    modal.style.display = 'flex';
}

function closeScrutinizeModal() {
    document.getElementById('scrutinize-modal').style.display = 'none';
}
</script>
@endsection
