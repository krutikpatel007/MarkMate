@extends('layouts.app')

@section('title', 'Hall Ticket Generator | Shreyarth University')
@section('page-title', 'Hall Ticket Generator')
@section('page-subtitle', 'Select a class section to view clearance certificates and generate candidate hall tickets')

@section('content')
<div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 100%);">
    <form method="get" action="{{ route('exam.hall-tickets.generator') }}" class="actions" style="gap: 1.5rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 20rem;">
            <label for="class_section_id" class="muted" style="font-weight: 700; font-size: 0.8125rem; display: block; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.05em;">Select Target Class Section</label>
            <select name="class_section_id" id="class_section_id" style="width: 100%;" onchange="this.form.submit()">
                <option value="">-- Choose Class Section --</option>
                @foreach($classSections as $cs)
                    <option value="{{ $cs->id }}" {{ (int)$selectedClassSectionId === (int)$cs->id ? 'selected' : '' }}>
                        {{ $cs->program->program_name }} - Sem {{ $cs->semester->semester_no }} ({{ $cs->section_name }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="button">Load Class Sheet</button>
            <a href="{{ route('exam.hall-tickets.index') }}" class="button secondary">Waiver Clearance Console</a>
        </div>
    </form>
</div>

@if($selectedClassSectionId)
    <section class="card">
        <div class="actions" style="justify-content: space-between; margin-bottom: 1rem;">
            <h2 style="margin-bottom: 0;">Candidate Admit Clearances</h2>
            <span class="badge success" style="padding: 0.4rem 0.875rem;">Total Enrolled Candidates: {{ $students->count() }}</span>
        </div>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                <tr>
                    <th style="width: 10%;">Roll No.</th>
                    <th style="width: 20%;">Enrollment No.</th>
                    <th style="width: 30%;">Student Name</th>
                    <th style="width: 15%;">Attendance %</th>
                    <th style="width: 15%;">Clearance Status</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    @php($waiver = $waivers->get($student->id))
                    @php($isCleared = ($student->percentage >= 75 || $waiver !== null) && $student->fee_paid)
                    <tr>
                        <td><strong>{{ $student->roll_no }}</strong></td>
                        <td>{{ $student->enrollment_no }}</td>
                        <td><strong>{{ $student->user->name }}</strong></td>
                        <td>
                            <span class="badge {{ $student->percentage < 75 ? 'danger' : 'success' }}" style="font-weight: 700;">
                                {{ $student->percentage }}%
                            </span>
                        </td>
                        <td>
                            @if($isCleared)
                                @if($waiver)
                                    <span class="badge success" style="text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em; font-weight: 600;">Cleared (Waiver)</span>
                                @else
                                    <span class="badge success" style="text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em; font-weight: 600;">Cleared (Normal)</span>
                                @endif
                            @else
                                <div style="display: flex; flex-direction: column; gap: 0.25rem; align-items: flex-start;">
                                    <span class="badge danger" style="text-transform: uppercase; font-size: 0.625rem; letter-spacing: 0.05em; font-weight: 600;">Blocked</span>
                                    @if(!$student->fee_paid)
                                        <span class="badge" style="text-transform: uppercase; font-size: 0.55rem; letter-spacing: 0.05em; font-weight: 700; background: rgba(245, 158, 11, 0.1); color: var(--color-scsa-gold);">Fee Unpaid</span>
                                    @endif
                                    @if($student->percentage < 75 && $waiver === null)
                                        <span class="badge danger" style="text-transform: uppercase; font-size: 0.55rem; letter-spacing: 0.05em; font-weight: 700; background: rgba(239, 68, 68, 0.1); color: var(--color-scsa-danger);">Low Attendance</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($isCleared)
                                <a href="{{ route('student.hall-ticket.download', ['student_id' => $student->id]) }}" class="button" target="_blank" style="padding: 0.4rem 0.8rem; font-size: 0.8125rem;">
                                    Print Admit Card
                                </a>
                            @else
                                <a href="{{ route('exam.hall-tickets.index', ['search' => $student->enrollment_no]) }}" class="button danger" style="padding: 0.4rem 0.8rem; font-size: 0.8125rem; text-decoration: none;">
                                    Manage Lock
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted" style="text-align: center; padding: 3rem 0;">
                            No active students found in this class section.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@else
    <div class="card" style="text-align: center; padding: 4rem 1rem;" class="muted">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 4rem; height: 4rem; color: var(--color-scsa-gold); margin: 0 auto 1rem auto; opacity: 0.6;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-7.377 2.24l-.407 1.63M3.177 12a48.095 48.095 0 00-1.107.08m1.107-.08L5.34 9m0 0l1.63-6.52c.074-.298.318-.518.625-.53a48.514 48.514 0 015.8-.08" />
        </svg>
        <h3>No Class Loaded</h3>
        <p class="muted" style="font-size: 0.9375rem; margin-top: 0.35rem; max-width: 25rem; margin-left: auto; margin-right: auto;">
            Please select a target program class section from the dropdown above to audit student clearance statuses.
        </p>
    </div>
@endif
@endsection
