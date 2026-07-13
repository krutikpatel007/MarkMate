@extends('layouts.app')

@section('title', 'Teacher Appraisals & Feedback | SCSA Attendance')
@section('page-title', 'Teacher Appraisals & Feedback')
@section('page-subtitle', 'Provide constructive feedback and ratings for your instructors to help improve course quality.')

@section('content')
    <!-- Status & Error Banners -->
    @if(session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem; animation: fadeIn 0.4s ease-in-out;">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-2" style="gap: 1.5rem; align-items: flex-start;">
        @forelse($assignments as $assignment)
            @php
                $hasFeedback = in_array($assignment->id, $submittedFeedbackIds);
                $existingFeedback = $hasFeedback ? \App\Models\FacultyFeedback::where('student_id', Auth::user()->studentProfile->id)->where('subject_assignment_id', $assignment->id)->first() : null;
            @endphp

            <div class="card" style="padding: 1.5rem; position: relative; border-top: 4px solid {{ $hasFeedback ? '#10b981' : 'var(--color-scsa-accent)' }};">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div>
                        <span class="badge secondary" style="font-size: 0.75rem; text-transform: uppercase;">{{ $assignment->subject->subject_code }}</span>
                        <h3 style="margin: 0.25rem 0 0; font-size: 1.15rem; font-weight: 700;">{{ $assignment->subject->subject_name }}</h3>
                        <div class="muted" style="margin-top: 0.15rem; font-size: 0.9rem;">Instructor: <strong>{{ $assignment->faculty->user->name }}</strong></div>
                    </div>
                    @if($hasFeedback)
                        <span class="badge success" style="padding: 0.4rem 0.8rem; font-weight: 700;">Submitted (★ {{ $existingFeedback->rating }}.0)</span>
                    @else
                        <span class="badge" style="background-color: #fef3c7; color: #d97706; padding: 0.4rem 0.8rem; font-weight: 700;">Awaiting Feedback</span>
                    @endif
                </div>

                @if(!$hasFeedback)
                    <form method="post" action="{{ route('student.feedback.store', $assignment->id) }}">
                        @csrf
                        <div class="field" style="margin-bottom: 1rem;">
                            <label style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Select Rating</label>
                            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.25rem;">
                                @for($i=1; $i<=5; $i++)
                                    <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 1rem; font-weight: 600; cursor: pointer; color: var(--color-scsa-ink);">
                                        <input type="radio" name="rating" value="{{ $i }}" {{ $i == 5 ? 'checked' : '' }} style="accent-color: var(--color-scsa-accent); width: 1.1rem; height: 1.1rem;">
                                        ★ {{ $i }}
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <div class="field" style="margin-bottom: 1rem;">
                            <label for="comments_{{ $assignment->id }}" style="font-weight: 600;">Comments & Suggestions</label>
                            <textarea id="comments_{{ $assignment->id }}" name="comments" placeholder="Write your review here..." rows="3" style="width: 100%; border: 1px solid var(--color-scsa-line); border-radius: 6px; padding: 0.5rem; font-size: 0.9rem;" required></textarea>
                        </div>

                        <button type="submit" class="button">Submit Appraisal</button>
                    </form>
                @else
                    <div style="background-color: var(--color-scsa-line); padding: 1rem; border-radius: 8px;" class="muted">
                        <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--color-scsa-ink);">Your Review:</div>
                        <p style="font-style: italic; font-size: 0.9rem; margin: 0;">"{{ $existingFeedback->comments }}"</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="card" style="grid-column: span 2; text-align: center; padding: 2rem;">
                <p class="muted">You do not have any active subject assignments to evaluate at this time.</p>
            </div>
        @endforelse
    </div>
@endsection
