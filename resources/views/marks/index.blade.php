@extends('layouts.app')

@section('title', 'Internal Marks | SCSA Attendance')
@section('page-title', 'Internal Marks')
@section('page-subtitle', 'Manage Mid-Sem and continuous evaluation marks')

@section('content')
    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(20rem, 1fr)); gap: 1.5rem;">
        @forelse($assignments as $index => $assignment)
            @php
                // Design custom gradient accent border per card for visual distinction
                $accents = [
                    'border-top-color: var(--color-scsa-accent);',
                    'border-top-color: #8b5cf6;',
                    'border-top-color: #ec4899;',
                    'border-top-color: #f59e0b;',
                    'border-top-color: #3b82f6;',
                ];
                $accent = $accents[$index % count($accents)];
            @endphp
            
            <div class="card subject-card" style="position: relative; overflow: hidden; display: flex; flex-direction: column; min-height: 16rem; padding: 1.5rem; {{ $accent }} border-top-width: 4px; box-shadow: var(--shadow-sm); transition: all 0.25s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow-sm)';">
                <!-- Massive background watermark for premium course portal appearance -->
                <div style="position: absolute; right: -15px; bottom: -20px; font-size: 5.5rem; font-weight: 900; color: var(--color-scsa-ink); opacity: 0.03; pointer-events: none; user-select: none; font-family: var(--font-display);">
                    {{ substr($assignment->subject->subject_code, 0, 3) }}
                </div>
                
                <!-- Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <span class="badge" style="background-color: var(--color-scsa-accent-soft); color: var(--color-scsa-accent);">
                        {{ $assignment->subject->subject_code }}
                    </span>
                    
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                        @if($assignment->config_status === 'unconfigured')
                            <span class="badge" style="background-color: rgba(239, 68, 68, 0.12); color: var(--color-scsa-danger); font-size: 0.65rem;">Unconfigured</span>
                        @elseif($assignment->config_status === 'draft')
                            <span class="badge" style="background-color: rgba(245, 158, 11, 0.12); color: var(--color-scsa-gold); font-size: 0.65rem;">Draft (Open)</span>
                        @else
                            <span class="badge" style="background-color: rgba(16, 185, 129, 0.12); color: var(--color-scsa-success); font-size: 0.65rem;">🔒 Submitted</span>
                            @if($assignment->external_marks_status === 'not_released')
                                <span class="badge" style="background-color: rgba(148, 163, 184, 0.12); color: #64748b; font-size: 0.6rem;">External: Locked</span>
                            @elseif($assignment->external_marks_status === 'released')
                                <span class="badge animate-pulse" style="background-color: rgba(245, 158, 11, 0.12); color: var(--color-scsa-gold); font-size: 0.6rem;">External: Open</span>
                            @elseif($assignment->external_marks_status === 'submitted')
                                <span class="badge" style="background-color: rgba(16, 185, 129, 0.12); color: var(--color-scsa-success); font-size: 0.6rem;">🔒 External Sub</span>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Subject Title -->
                <h3 style="font-size: 1.15rem; font-weight: 800; line-height: 1.35; color: var(--color-scsa-ink); margin: 0 0 0.75rem 0;">
                    {{ $assignment->subject->subject_name }}
                </h3>

                <!-- Class & Faculty Details -->
                <div style="font-size: 0.85rem; color: var(--color-scsa-muted); font-weight: 500; display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1.5rem; z-index: 1;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span>🏫</span>
                        <span>Class: <strong style="color: var(--color-scsa-ink);">{{ $assignment->classSection->display_name }}</strong></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span>🧑‍🏫</span>
                        <span>Faculty: <strong style="color: var(--color-scsa-ink);">{{ $assignment->faculty->user->name }}</strong></span>
                    </div>
                </div>

                <!-- Custom Progress Bar representation based on state -->
                <div style="margin-bottom: 1.5rem; margin-top: auto; z-index: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--color-scsa-muted); margin-bottom: 0.35rem;">
                        <span>Setup Progress</span>
                        <span>
                            @if($assignment->config_status === 'unconfigured') 0% @elseif($assignment->config_status === 'draft') 50% @else 100% @endif
                        </span>
                    </div>
                    <div style="width: 100%; height: 0.375rem; background-color: var(--color-scsa-line); border-radius: 99px; overflow: hidden;">
                        @php
                            $width = $assignment->config_status === 'unconfigured' ? '8%' : ($assignment->config_status === 'draft' ? '50%' : '100%');
                            $color = $assignment->config_status === 'unconfigured' ? 'var(--color-scsa-danger)' : ($assignment->config_status === 'draft' ? 'var(--color-scsa-gold)' : 'var(--color-scsa-success)');
                        @endphp
                        <div style="width: {{ $width }}; height: 100%; background-color: {{ $color }}; border-radius: 99px;"></div>
                    </div>
                </div>

                <!-- Premium Action Buttons -->
                <div style="border-top: 1px solid var(--color-scsa-line); padding-top: 1rem; display: flex; gap: 0.5rem; justify-content: flex-end; z-index: 1;">
                    @if($assignment->config_status === 'unconfigured')
                        @if(auth()->user()->isFaculty())
                            <a class="button" href="{{ route('marks.configure.create', $assignment) }}" style="width: 100%; gap: 0.375rem;">
                                ⚙️ Configure Components
                            </a>
                        @else
                            <span class="muted" style="font-size: 0.8125rem; font-style: italic; align-self: center; width: 100%; text-align: center;">Awaiting faculty configuration</span>
                        @endif
                    @else
                        <a class="button secondary" href="{{ route('marks.show', $assignment) }}" style="flex: 1; text-align: center; justify-content: center; gap: 0.35rem;">
                            @if($assignment->config_status === 'submitted')
                                @if($assignment->external_marks_status === 'released' && auth()->user()->isFaculty())
                                    ✍️ Enter External Marks
                                @else
                                    👁️ View Gradesheet
                                @endif
                            @else
                                ✍️ Enter &amp; Grade
                            @endif
                        </a>
                        @if($assignment->config_status === 'draft' && auth()->user()->isFaculty())
                            <a class="button" href="{{ route('marks.configure.create', $assignment) }}" style="background-color: var(--color-scsa-gold); border-color: var(--color-scsa-gold); min-width: 2.375rem; padding: 0.5rem;" title="Edit evaluation columns">
                                ⚙️
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 4rem 1.5rem;">
                <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">📭</span>
                <h2 style="font-weight: 700; color: var(--color-scsa-accent); border-bottom: 0; padding-bottom: 0; margin-bottom: 0.5rem;">No Subjects Assigned</h2>
                <p class="muted" style="max-width: 28rem; margin: 0 auto;">No subjects or faculty assignments are available in this academic semester yet.</p>
            </div>
        @endforelse
    </div>
@endsection
