@extends('layouts.app')

@section('title', 'Hall Ticket Clearance | Shreyarth University')
@section('page-title', 'Hall Ticket Clearance')
@section('page-subtitle', 'Manage attendance waivers and lock/unlock admit cards for low-attendance students')

@section('content')
<div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 100%);">
    <form method="get" action="{{ route('exam.hall-tickets.index') }}" class="actions" style="gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 15rem;">
            <label for="search" class="muted" style="font-size: 0.8125rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Search Student</label>
            <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Enrollment No. or Name..." style="width: 100%;">
        </div>
        <div style="width: 15rem;">
            <label for="program_id" class="muted" style="font-size: 0.8125rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Filter by Program</label>
            <select name="program_id" id="program_id" style="width: 100%;">
                <option value="">All Programs</option>
                @foreach($programs as $p)
                    <option value="{{ $p->id }}" {{ (int)$programFilter === (int)$p->id ? 'selected' : '' }}>{{ $p->program_name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; align-items: flex-end; gap: 0.5rem; margin-top: 1.25rem;">
            <button type="submit" class="button">Apply Filters</button>
            <a href="{{ route('exam.hall-tickets.index') }}" class="button secondary">Clear</a>
        </div>
    </form>
</div>

<section class="card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 1rem;">
        <h2 style="margin-bottom: 0;">Attendance Defaulters (< 75%)</h2>
        <span class="badge danger" style="padding: 0.4rem 0.875rem;">Total Defaulters: {{ $defaulters->count() }}</span>
    </div>

    <div style="overflow-x: auto;">
        <table>
            <thead>
            <tr>
                <th>Enrollment No.</th>
                <th>Roll No.</th>
                <th>Student Name</th>
                <th>Attendance %</th>
                <th>Waiver Status</th>
                <th>Reason / Granted By</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($defaulters as $student)
                @php($waiver = $waivers->get($student->id))
                <tr>
                    <td><strong>{{ $student->enrollment_no }}</strong></td>
                    <td>{{ $student->roll_no }}</td>
                    <td>{{ $student->name }}</td>
                    <td>
                        <span class="badge danger" style="font-weight: 700;">{{ $student->percentage }}%</span>
                    </td>
                    <td>
                        @if($waiver)
                            <span class="badge success" style="text-transform: uppercase; letter-spacing: 0.04em;">Waiver Active</span>
                        @else
                            <span class="badge danger" style="text-transform: uppercase; letter-spacing: 0.04em;">Ticket Locked</span>
                        @endif
                    </td>
                    <td>
                        @if($waiver)
                            <div style="font-weight: 500; color: var(--color-scsa-ink);">{{ $waiver->reason }}</div>
                            <div class="muted" style="font-size: 0.75rem;">By: {{ $waiver->grantor->name }}</div>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($waiver)
                            <form method="post" action="{{ route('exam.hall-tickets.destroy-waiver', $student->id) }}" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button danger" onclick="return confirm('Are you sure you want to revoke this waiver?')">Revoke Waiver</button>
                            </form>
                        @else
                            <button type="button" class="button" onclick="openWaiverModal({{ $student->id }}, '{{ addslashes($student->name) }}')">Grant Waiver</button>
                        @endif
                        <a href="{{ route('student.hall-ticket.download', ['student_id' => $student->id]) }}" class="button secondary" style="margin-left: 0.25rem;" target="_blank">Print ticket</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align: center; padding: 3rem 0;">
                        No low-attendance defaulters matching active criteria found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- Waiver Dialog Modal -->
<div id="waiver-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 28rem; padding: 1.5rem; position: relative; border-radius: var(--border-radius-lg);">
        <h3 style="margin-bottom: 0.5rem;">Grant Attendance Waiver</h3>
        <p class="muted" style="font-size: 0.875rem; margin-bottom: 1rem;">
            You are unlocking the exam hall ticket clearance for student: <strong id="modal-student-name"></strong>.
        </p>
        <form id="waiver-form" method="post" action="">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label for="reason" style="font-weight: 600; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem;">Official Waiver Reason</label>
                <textarea name="reason" id="reason" rows="3" required placeholder="e.g. Approved medical case, sports representative, Dean override..." style="width: 100%; font-family: inherit; border-radius: 4px; border: 1px solid var(--color-scsa-line);"></textarea>
            </div>
            <div class="actions" style="justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="button secondary" onclick="closeWaiverModal()">Cancel</button>
                <button type="submit" class="button">Clear Admit Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function openWaiverModal(studentId, studentName) {
    const modal = document.getElementById('waiver-modal');
    const nameSpan = document.getElementById('modal-student-name');
    const form = document.getElementById('waiver-form');
    
    nameSpan.innerText = studentName;
    form.action = `/exam/hall-tickets/${studentId}/waiver`;
    modal.style.display = 'flex';
}

function closeWaiverModal() {
    document.getElementById('waiver-modal').style.display = 'none';
}
</script>
@endsection
