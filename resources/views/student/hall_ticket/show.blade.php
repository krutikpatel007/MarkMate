@extends('layouts.app')

@section('title', 'Examination Hall Ticket | Shreyarth University')
@section('page-title', 'Hall Ticket Clearance')
@section('page-subtitle', 'Check your end-semester examination admit card clearance certificate')

@section('content')
<div style="max-width: 45rem; margin: 0 auto;">
    @if($isEligible)
        <div class="card" style="border-left: 5px solid var(--color-scsa-success); padding: 2rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 3.5rem; height: 3.5rem; border-radius: 50%; background: rgba(16, 185, 129, 0.1); color: var(--color-scsa-success); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 2rem; height: 2rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div>
                    <h2 style="margin: 0; color: var(--color-scsa-success);">Clearance Certificate Issued</h2>
                    <div style="font-weight: 700; color: var(--color-scsa-gold); text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem; margin-top: 0.15rem;">Exam Admit Clearance: Cleared</div>
                </div>
            </div>

            <p style="font-size: 0.95rem; line-height: 1.6;" class="muted">
                Congratulations! You have been officially cleared to sit for the end-semester examinations. Your hall ticket is fully generated and ready.
            </p>

            @if($hasWaiver)
                <div style="margin: 1rem 0; padding: 0.75rem 1rem; background: rgba(16, 185, 129, 0.05); border-left: 3px solid var(--color-scsa-success); border-radius: 4px; font-size: 0.8125rem; color: var(--color-scsa-success); font-weight: 500;">
                    💡 Clearance granted via Coordinator attendance override: <strong>"{{ $student->examWaiver->reason }}"</strong>.
                </div>
            @else
                <div style="margin: 1rem 0; padding: 0.75rem 1rem; background: rgba(4, 120, 87, 0.05); border-left: 3px solid var(--color-scsa-success); border-radius: 4px; font-size: 0.8125rem; color: var(--color-scsa-success); font-weight: 500;">
                    🎉 attendance clearance achieved successfully: <strong>{{ $percentage }}%</strong> (Requirement: 75%).
                </div>
            @endif

            <div style="border-top: 1px solid var(--color-scsa-line); padding-top: 1.25rem; margin-top: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div class="muted" style="font-size: 0.8125rem;">
                    Please print and bring this admit card to all examination blocks.
                </div>
                <a href="{{ route('student.hall-ticket.download') }}" class="button" style="text-decoration: none; padding: 0.65rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.15rem; height: 1.15rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0a2.25 2.25 0 01-2.25 2.25H8.59A2.25 2.25 0 016.34 18m11.318-4.171A3 3 0 0015 11.25H9a3 3 0 00-2.682 1.579m11.318 0c.07-.013.14-.025.21-.038A2.25 2.25 0 0019.5 10.5a8.966 8.966 0 00-3.329-6.96 3 3 0 00-3.003-.137l-1.077.539a3 3 0 01-2.684 0l-1.077-.539a3 3 0 00-3.003.137A8.966 8.966 0 004.5 10.5a2.25 2.25 0 001.372 2.06c.07.013.14.025.21.038" />
                    </svg>
                    Download Print-Ready PDF
                </a>
            </div>
        </div>

        <section class="card" style="padding: 1.5rem;">
            <h3 style="margin-bottom: 0.75rem;">Interactive Hall Ticket Specimen</h3>
            <div style="border: 1px dashed var(--color-scsa-line); border-radius: var(--border-radius-md); padding: 1.5rem; text-align: center; background: rgba(0,0,0,0.01);">
                <div style="max-width: 32rem; margin: 0 auto; text-align: left; padding: 1rem; border: 1px solid var(--color-scsa-line); border-radius: 4px; background: #fff;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid var(--color-scsa-ink); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                        <div>
                            <strong style="font-size: 0.875rem;">SHREYARTH UNIVERSITY</strong>
                            <div class="muted" style="font-size: 0.625rem;">END-SEMESTER EXAMS HALL TICKET</div>
                        </div>
                        <span style="font-weight: 700; font-size: 0.6875rem; color: var(--color-scsa-success);">CLEARED</span>
                    </div>
                    <div class="grid grid-2" style="font-size: 0.75rem; margin-bottom: 0.75rem;">
                        <div>
                            <div><span class="muted">Student:</span> <strong>{{ $student->user->name }}</strong></div>
                            <div><span class="muted">Enrollment:</span> {{ $student->enrollment_no }}</div>
                        </div>
                        <div>
                            <div><span class="muted">Class:</span> {{ $student->classSection->display_name }}</div>
                            <div><span class="muted">Semester:</span> {{ $student->semester->semester_no }}</div>
                        </div>
                    </div>
                    <div style="font-size: 0.625rem; text-align: center;" class="muted">
                        <em>This is a preview representation. Click download to fetch the official stamp document.</em>
                    </div>
                </div>
            </div>
        </section>
    @else
        <div class="card" style="border-left: 5px solid var(--color-scsa-danger); padding: 2rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 3.5rem; height: 3.5rem; border-radius: 50%; background: rgba(239, 68, 68, 0.1); color: var(--color-scsa-danger); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 2rem; height: 2rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <div>
                    <h2 style="margin: 0; color: var(--color-scsa-danger);">Hall Ticket Locked</h2>
                    <div style="font-weight: 700; color: var(--color-scsa-danger); text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem; margin-top: 0.15rem;">Exam Admit Clearance: Blocked</div>
                </div>
            </div>

            <p style="font-size: 0.95rem; line-height: 1.6;" class="muted">
                Your end-semester hall ticket is currently **locked** because your overall attendance rate is **{{ $percentage }}%**, which falls below the mandatory university minimum criteria of **75%**.
            </p>

            <div style="margin: 1rem 0; padding: 0.75rem 1rem; background: rgba(185, 28, 28, 0.05); border-left: 3px solid var(--color-scsa-danger); border-radius: 4px; font-size: 0.8125rem; color: var(--color-scsa-danger); font-weight: 600;">
                ⚠️ Action Required: You must attend the next <strong>{{ $overallToAttend }}</strong> consecutive lectures to recover your eligibility.
            </div>

            <div style="border-top: 1px solid var(--color-scsa-line); padding-top: 1.25rem; margin-top: 1.5rem; font-size: 0.8125rem;" class="muted">
                If you have legitimate medical reasons or representing leaves approved by the Dean, please contact the **Central Examination Department** to obtain an official attendance override waiver.
            </div>
        </div>
    @endif
</div>
@endsection
