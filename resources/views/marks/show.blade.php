@extends('layouts.app')

@php
    $user = auth()->user();
    $isExamDept = $user->facultyProfile?->department?->department_code === 'EXAM';
    $isFaculty = $user->isFaculty() && (int) $subjectAssignment->faculty_id === (int) $user->facultyProfile?->id;
    $isAdmin = $user->isAdmin();
    
    $isExternalEnabled = in_array($subjectAssignment->external_marks_status, ['released', 'submitted']);
    $isExternalEditable = ($subjectAssignment->external_marks_status === 'released') && ($isFaculty || $isAdmin);
    
    $canReleaseExternal = ($isAdmin || ($user->isHod() && $isExamDept)) 
        && ($status === 'submitted_to_exam' || $status === 'submitted') 
        && ($subjectAssignment->external_marks_status === 'not_released');
        
    $showTabbedView = $isFaculty && !$user->isHod() && !$isAdmin && !$isExamDept;
@endphp

@section('title', 'Enter Internal Marks | SCSA Attendance')
@section('page-title', 'Internal Marks Sheet')
@section('page-subtitle')
    {{ $subjectAssignment->subject->subject_code }} — {{ $subjectAssignment->subject->subject_name }}
    | {{ $subjectAssignment->classSection->display_name }}
@endsection

@section('page-actions')
    <div class="actions" style="display: flex; gap: 0.5rem; align-items: center;">
        @php
            $showUnlock = false;
            
            if ($user->isAdmin()) {
                $showUnlock = ($status === 'submitted_to_hod' || $status === 'submitted_to_exam' || $status === 'submitted');
            } elseif ($user->isHod()) {
                if ($isExamDept) {
                    $showUnlock = ($status === 'submitted_to_exam' || $status === 'submitted');
                } else {
                    $showUnlock = ($status === 'submitted_to_hod');
                }
            }
        @endphp

        @if($canReleaseExternal)
            <form method="post" action="{{ route('marks.release-external', $subjectAssignment) }}" style="display: inline-block;" onsubmit="return confirm('Release external marks entry for this subject? Faculty will be allowed to enter end-sem marks.');">
                @csrf
                <button type="submit" class="button" style="background-color: var(--color-scsa-accent); border-color: var(--color-scsa-accent);">📢 Release External Marks</button>
            </form>
        @endif
        
        @if($showUnlock)
            <form method="post" action="{{ route('marks.unlock', $subjectAssignment) }}" style="display: inline-block;" onsubmit="return confirm('Unlock these internal marks? Faculty will be allowed to edit them again.');">
                @csrf
                <button type="submit" class="button" style="background-color: var(--color-scsa-gold); border-color: var(--color-scsa-gold);">🔓 Unlock Marks</button>
            </form>
        @endif

        @if($status === 'submitted_to_hod' && $user->isHod() && !$isExamDept)
            <form method="post" action="{{ route('marks.submit-to-exam', $subjectAssignment) }}" style="display: inline-block;" onsubmit="return confirm('Submit these marks to the Examination Department? This will lock the marksheet and transfer ownership.');">
                @csrf
                <button type="submit" class="button" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success);">📤 Submit to Exam Dept</button>
            </form>
        @endif

        <a class="button secondary" href="{{ route('marks.export', $subjectAssignment) }}">📥 Export Marks</a>
        <a class="button secondary" href="{{ route('marks.index') }}">Back to List</a>
    </div>
@endsection

@section('content')
    <!-- Statistics Cards Panel -->
    <div class="grid grid-4" style="margin-bottom: 2rem; gap: 1.25rem;">
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--color-scsa-accent); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: center;">
            <div class="muted" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Highest Marks (50)</div>
            <strong class="stat" style="color: var(--color-scsa-accent); font-size: 2rem;">{{ $stats['highest'] }}</strong>
        </div>
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--color-scsa-gold); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: center;">
            <div class="muted" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Lowest Marks (50)</div>
            <strong class="stat" style="color: var(--color-scsa-gold); font-size: 2rem;">{{ $stats['lowest'] }}</strong>
        </div>
        <div class="card" style="padding: 1.25rem; border-left: 4px solid #3b82f6; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: center;">
            <div class="muted" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Class Average</div>
            <strong class="stat" style="color: #3b82f6; font-size: 2rem;">{{ $stats['average'] }}</strong>
        </div>
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--color-scsa-success); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: center;">
            <div class="muted" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Pass Percentage (>=20)</div>
            <strong class="stat" style="color: var(--color-scsa-success); font-size: 2rem;">{{ $stats['pass_percentage'] }}%</strong>
        </div>
    </div>

    <!-- Status Banners -->
    @if($subjectAssignment->external_marks_status === 'submitted')
        <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: var(--border-radius-lg); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; color: var(--color-scsa-success); font-weight: 600; font-size: 0.9375rem; box-shadow: var(--shadow-sm);">
            <span style="font-size: 1.35rem; line-height: 1;">🎓</span>
            <span>External Marks Submitted: Both internal (50) and external (50) marks are officially submitted and finalized. Combined scorecard is visible to students.</span>
        </div>
    @elseif($subjectAssignment->external_marks_status === 'released')
        <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: var(--border-radius-lg); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; color: var(--color-scsa-gold); font-weight: 600; font-size: 0.9375rem; box-shadow: var(--shadow-sm);">
            <span style="font-size: 1.35rem; line-height: 1;">✍️</span>
            <span>External Marks Entry Released: The Exam Department has released the external marks entry. Faculty should enter the end-semester paper marks (out of 50).</span>
        </div>
    @endif

    @if($status === 'submitted_to_exam' || $status === 'submitted')
        <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: var(--border-radius-lg); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; color: var(--color-scsa-success); font-weight: 600; font-size: 0.9375rem; box-shadow: var(--shadow-sm);">
            <span style="font-size: 1.35rem; line-height: 1;">🎓</span>
            <span>Submitted to Exam Department: These marks are officially submitted and locked. Central Exam Department can unlock if corrections are required.</span>
        </div>
    @elseif($status === 'submitted_to_hod')
        <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: var(--border-radius-lg); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; color: #3b82f6; font-weight: 600; font-size: 0.9375rem; box-shadow: var(--shadow-sm);">
            <span style="font-size: 1.35rem; line-height: 1;">🔒</span>
            <span>Submitted to HOD: These marks are currently under review by the academic department HOD. HOD can unlock them or submit them to the Exam Department.</span>
        </div>
    @else
        <div style="background: rgba(45, 212, 191, 0.08); border: 1px solid rgba(45, 212, 191, 0.2); border-radius: var(--border-radius-lg); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; color: var(--color-scsa-accent); font-weight: 600; font-size: 0.9375rem; box-shadow: var(--shadow-sm);">
            <span style="font-size: 1.35rem; line-height: 1;">📝</span>
            <span>Draft Mode: Save your changes as draft as many times as you like. When finished, click Final Submit to submit the marks to your HOD.</span>
        </div>
    @endif

    @if($isExternalEditable)
        <div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">📋</span>
                <div>
                    <h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--color-scsa-ink); border: 0; padding-bottom: 0;">Bulk Import External Marks</h3>
                    <p class="muted" style="margin: 0.15rem 0 0 0; font-size: 0.8125rem;">Download template, enter marks, and upload back to populate the gradesheet in bulk.</p>
                </div>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <a class="button secondary" href="{{ route('marks.import-external-template', $subjectAssignment) }}" style="padding: 0.55rem 1.25rem; font-size: 0.875rem;">📥 Download Template</a>
                
                <form method="post" action="{{ route('marks.import-external', $subjectAssignment) }}" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 0.5rem;">
                    @csrf
                    <input type="file" name="csv_file" accept=".csv" required style="font-size: 0.8125rem; border: 1px solid var(--color-scsa-line); padding: 0.35rem; border-radius: var(--border-radius-md); max-width: 15rem; background: var(--bg-secondary);">
                    <button type="submit" class="button" style="padding: 0.55rem 1.25rem; font-size: 0.875rem; background-color: var(--color-scsa-accent); border-color: var(--color-scsa-accent);">📤 Upload CSV</button>
                </form>
            </div>
        </div>
    @endif

    <div class="card" style="padding: 0; overflow: hidden; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-md);">
        @if($showTabbedView)
            <!-- Tabs Navigation -->
            <div class="tabs-container" style="display: flex; gap: 0.5rem; background: var(--bg-secondary); padding: 0.5rem 0.5rem 0 0.5rem; border-bottom: 1px solid var(--color-scsa-line);">
                <button type="button" class="tab-btn active" data-tab="internal" style="padding: 0.75rem 1.25rem; font-weight: 700; font-size: 0.875rem; border: 0; background: none; border-bottom: 3px solid var(--color-scsa-accent); color: var(--color-scsa-accent); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                    📝 Internal Evaluation (CIE)
                </button>
                <button type="button" class="tab-btn" data-tab="external" style="padding: 0.75rem 1.25rem; font-weight: 700; font-size: 0.875rem; border: 0; background: none; border-bottom: 3px solid transparent; color: var(--color-scsa-muted); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                    🎓 End-Semester Evaluation (External)
                </button>
            </div>

            <form method="post" action="{{ $isExternalEditable ? route('marks.store-external', $subjectAssignment) : route('marks.store', $subjectAssignment) }}" id="marks-form">
                @csrf

                <!-- SECTION 1: Internal Evaluation -->
                <div id="internal-section" class="tab-content">
                    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; max-height: 32rem;">
                        <table class="marks-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                            <thead style="position: sticky; top: 0; z-index: 5;">
                            <tr>
                                <th style="width: 110px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Roll No.</th>
                                <th style="min-width: 180px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Enrollment No.</th>
                                
                                <!-- Mid Sem Raw Header -->
                                <th style="width: 130px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Mid Sem<br>Marks (30)</th>
                                <!-- Mid Sem Scaled Header -->
                                <th style="width: 130px; text-align: center; background: linear-gradient(rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.02)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line); color: var(--color-scsa-muted);">Mid Sem<br>Scaled (20)</th>
                                
                                <!-- Dynamic Component Headers -->
                                @foreach($components as $comp)
                                    <th style="width: 135px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">
                                        {{ $comp->name }}<br>({{ (int)$comp->max_marks }})
                                    </th>
                                @endforeach
                                
                                <!-- CIE Total Header -->
                                <th style="width: 130px; text-align: center; background: linear-gradient(rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.02)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line); color: var(--color-scsa-muted);">CIE Total<br>(30)</th>
                                <!-- Grand Total Header -->
                                <th style="width: 130px; text-align: center; background: linear-gradient(rgba(16, 185, 129, 0.04), rgba(16, 185, 129, 0.04)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); font-weight: 800; color: var(--color-scsa-success);">Total Marks<br>(50)</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($students as $student)
                                @php
                                    $markRecord = $marks->get($student->id);
                                    $cieValues = $markRecord ? $markRecord->componentValues->keyBy('internal_marks_component_id') : collect();
                                @endphp
                                <tr class="student-row" data-student-id="{{ $student->id }}">
                                    <td style="border-right: 1px solid var(--color-scsa-line); font-weight: 700;">{{ $student->roll_no }}</td>
                                    <td style="border-right: 1px solid var(--color-scsa-line); padding: 0.75rem 1rem; font-weight: 700; color: var(--color-scsa-ink);">
                                        {{ $student->enrollment_no }}
                                    </td>
                                    <td style="border-right: 1px solid var(--color-scsa-line);">
                                        @if($isEditable)
                                            <input type="number" step="0.01" min="0" max="30" 
                                                   name="mid_sem_30[{{ $student->id }}]"
                                                   class="mid-sem-input text-center" 
                                                   style="width: 90px; text-align: center; padding: 0.45rem 0.5rem;"
                                                   value="{{ $markRecord ? $markRecord->mid_sem_30 : '' }}">
                                        @else
                                            <div class="text-center" style="font-weight: 700; color: var(--color-scsa-ink);">{{ $markRecord && $markRecord->mid_sem_30 !== null ? $markRecord->mid_sem_30 : '-' }}</div>
                                        @endif
                                    </td>
                                    <td style="background: rgba(0, 0, 0, 0.012); border-right: 1px solid var(--color-scsa-line);">
                                        <div class="mid-sem-scaled text-center" style="font-weight: 700; color: var(--color-scsa-muted);">
                                            {{ $markRecord && $markRecord->mid_sem_20 !== null ? $markRecord->mid_sem_20 : '0.00' }}
                                        </div>
                                    </td>
                                    @foreach($components as $comp)
                                        @php
                                            $valRecord = $cieValues->get($comp->id);
                                        @endphp
                                        <td style="border-right: 1px solid var(--color-scsa-line);">
                                            @if($isEditable)
                                                <input type="number" step="0.01" min="0" max="{{ $comp->max_marks }}" 
                                                       name="comp_marks[{{ $student->id }}][{{ $comp->id }}]"
                                                       class="comp-input text-center" 
                                                       data-comp-id="{{ $comp->id }}"
                                                       data-max-marks="{{ $comp->max_marks }}"
                                                       style="width: 90px; text-align: center; padding: 0.45rem 0.5rem;"
                                                       value="{{ $valRecord ? $valRecord->marks_obtained : '' }}">
                                            @else
                                                <div class="text-center" style="font-weight: 700; color: var(--color-scsa-ink);">{{ $valRecord && $valRecord->marks_obtained !== null ? $valRecord->marks_obtained : '-' }}</div>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="background: rgba(0, 0, 0, 0.012); border-right: 1px solid var(--color-scsa-line);">
                                        <div class="cie-total text-center" style="font-weight: 700; color: var(--color-scsa-muted);">
                                            {{ $markRecord ? $markRecord->cie_30 : '0.00' }}
                                        </div>
                                    </td>
                                    <td style="background: rgba(16, 185, 129, 0.04);">
                                        <div class="grand-total text-center" style="font-weight: 800; color: var(--color-scsa-success); font-family: var(--font-display); font-size: 1.05rem;">
                                            {{ $markRecord ? $markRecord->total_50 : '0.00' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($isEditable)
                        <div class="actions" style="padding: 1.5rem; background: var(--bg-primary); border-top: 1px solid var(--color-scsa-line); display: flex; gap: 1rem;">
                            <button class="button" type="submit" name="action" value="save" style="padding: 0.65rem 1.5rem;">Save Draft</button>
                            <button class="button" type="button" id="submit-btn" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success); padding: 0.65rem 1.5rem;">Submit to HOD</button>
                        </div>
                    @endif
                </div>

                <!-- SECTION 2: End-Semester Evaluation -->
                <div id="external-section" class="tab-content" style="display: none;">
                    @if($isExternalEnabled)
                        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; max-height: 32rem;">
                            <table class="marks-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                                <thead style="position: sticky; top: 0; z-index: 5;">
                                <tr>
                                    <th style="width: 110px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Roll No.</th>
                                    <th style="min-width: 180px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Enrollment No.</th>
                                    <th style="width: 150px; text-align: center; background: linear-gradient(rgba(16, 185, 129, 0.04), rgba(16, 185, 129, 0.04)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line); font-weight: 800; color: var(--color-scsa-success);">Internal Marks<br>(50)</th>
                                    <th style="width: 150px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">External<br>Marks (50)</th>
                                    <th style="width: 150px; text-align: center; background: linear-gradient(rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.08)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); font-weight: 800; color: var(--color-scsa-success);">Total Marks<br>(100)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($students as $student)
                                    @php
                                        $markRecord = $marks->get($student->id);
                                    @endphp
                                    <tr class="student-row" data-student-id="{{ $student->id }}">
                                        <td style="border-right: 1px solid var(--color-scsa-line); font-weight: 700;">{{ $student->roll_no }}</td>
                                        <td style="border-right: 1px solid var(--color-scsa-line); padding: 0.75rem 1rem; font-weight: 700; color: var(--color-scsa-ink);">
                                            {{ $student->enrollment_no }}
                                        </td>
                                        <td style="background: rgba(16, 185, 129, 0.02); border-right: 1px solid var(--color-scsa-line);">
                                            <div class="grand-total text-center" style="font-weight: 700; color: var(--color-scsa-muted);">
                                                {{ $markRecord ? $markRecord->total_50 : '0.00' }}
                                            </div>
                                        </td>
                                        <td style="border-right: 1px solid var(--color-scsa-line);">
                                            @if($isExternalEditable)
                                                <input type="number" step="0.01" min="0" max="50" 
                                                       name="external_marks[{{ $student->id }}]"
                                                       class="external-input text-center" 
                                                       style="width: 90px; text-align: center; padding: 0.45rem 0.5rem;"
                                                       value="{{ $markRecord ? $markRecord->external_50 : '' }}">
                                            @else
                                                <div class="text-center external-input-display" style="font-weight: 700; color: var(--color-scsa-ink);">{{ $markRecord && $markRecord->external_50 !== null ? $markRecord->external_50 : '-' }}</div>
                                            @endif
                                        </td>
                                        <td style="background: rgba(16, 185, 129, 0.08);">
                                            <div class="total-100 text-center" style="font-weight: 800; color: var(--color-scsa-success); font-family: var(--font-display); font-size: 1.05rem;">
                                                {{ $markRecord && $markRecord->total_100 !== null ? $markRecord->total_100 : '0.00' }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($isExternalEditable)
                            <div class="actions" style="padding: 1.5rem; background: var(--bg-primary); border-top: 1px solid var(--color-scsa-line); display: flex; gap: 1rem;">
                                <button class="button" type="submit" name="action" value="save" style="padding: 0.65rem 1.5rem;">Save External Draft</button>
                                <button class="button" type="button" id="submit-external-btn" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success); padding: 0.65rem 1.5rem;">Submit External Marks</button>
                            </div>
                        @endif
                    @else
                        <div style="padding: 4rem 2rem; text-align: center; background: var(--bg-secondary); border-radius: var(--border-radius-lg); margin: 2rem;">
                            <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">🔒</span>
                            <h2 style="margin-bottom: 0.5rem; font-weight: 700; color: var(--color-scsa-accent-deep); border: 0; padding-bottom: 0;">End-Semester Marks Entry Locked</h2>
                            <p class="muted" style="max-width: 32rem; margin: 0 auto; font-size: 0.9375rem; line-height: 1.5;">
                                The Examination Department has not released the external end-semester marks entry for this subject yet. 
                                Once the internal evaluation marks are finalized and submitted to the Exam Department, the entry can be released.
                            </p>
                        </div>
                    @endif
                </div>
            </form>

        @else
            <!-- Combined Sheet View (HOD, Admin, Exam Dept) -->
            <form method="post" action="{{ $isExternalEditable ? route('marks.store-external', $subjectAssignment) : route('marks.store', $subjectAssignment) }}" id="marks-form">
                @csrf
                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; max-height: 32rem;">
                    <table class="marks-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                        <thead style="position: sticky; top: 0; z-index: 5;">
                        <tr>
                            <th style="width: 110px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Roll No.</th>
                            <th style="min-width: 180px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Enrollment No.</th>
                            
                            <!-- Mid Sem Raw Header -->
                            <th style="width: 130px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Mid Sem<br>Marks (30)</th>
                            <!-- Mid Sem Scaled Header -->
                            <th style="width: 130px; text-align: center; background: linear-gradient(rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.02)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line); color: var(--color-scsa-muted);">Mid Sem<br>Scaled (20)</th>
                            
                            <!-- Dynamic Component Headers -->
                            @foreach($components as $comp)
                                <th style="width: 135px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">
                                    {{ $comp->name }}<br>({{ (int)$comp->max_marks }})
                                </th>
                            @endforeach
                            
                            <!-- CIE Total Header -->
                            <th style="width: 130px; text-align: center; background: linear-gradient(rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.02)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line); color: var(--color-scsa-muted);">CIE Total<br>(30)</th>
                            <!-- CIE Grand Total (50) Header -->
                            <th style="width: 130px; text-align: center; background: linear-gradient(rgba(16, 185, 129, 0.04), rgba(16, 185, 129, 0.04)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line); font-weight: 800; color: var(--color-scsa-success);">Internal Marks<br>(50)</th>
                            
                            @if($isExternalEnabled)
                                <!-- External Header -->
                                <th style="width: 130px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">External<br>Marks (50)</th>
                                <!-- Grand Total (100) Header -->
                                <th style="width: 130px; text-align: center; background: linear-gradient(rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.08)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); font-weight: 800; color: var(--color-scsa-success);">Total Marks<br>(100)</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($students as $student)
                            @php
                                $markRecord = $marks->get($student->id);
                                $cieValues = $markRecord ? $markRecord->componentValues->keyBy('internal_marks_component_id') : collect();
                            @endphp
                            <tr class="student-row" data-student-id="{{ $student->id }}">
                                <td style="border-right: 1px solid var(--color-scsa-line); font-weight: 700;">{{ $student->roll_no }}</td>
                                <td style="border-right: 1px solid var(--color-scsa-line); padding: 0.75rem 1rem; font-weight: 700; color: var(--color-scsa-ink);">
                                    {{ $student->enrollment_no }}
                                </td>
                                
                                <!-- Mid Sem Raw Marks Input -->
                                <td style="border-right: 1px solid var(--color-scsa-line);">
                                    @if($isEditable)
                                        <input type="number" step="0.01" min="0" max="30" 
                                               name="mid_sem_30[{{ $student->id }}]"
                                               class="mid-sem-input text-center" 
                                               style="width: 90px; text-align: center; padding: 0.45rem 0.5rem;"
                                               value="{{ $markRecord ? $markRecord->mid_sem_30 : '' }}">
                                    @else
                                        <div class="text-center" style="font-weight: 700; color: var(--color-scsa-ink);">{{ $markRecord && $markRecord->mid_sem_30 !== null ? $markRecord->mid_sem_30 : '-' }}</div>
                                    @endif
                                </td>
                                
                                <!-- Mid Sem Scaled Marks Display -->
                                <td style="background: rgba(0, 0, 0, 0.012); border-right: 1px solid var(--color-scsa-line);">
                                    <div class="mid-sem-scaled text-center" style="font-weight: 700; color: var(--color-scsa-muted);">
                                        {{ $markRecord && $markRecord->mid_sem_20 !== null ? $markRecord->mid_sem_20 : '0.00' }}
                                    </div>
                                </td>
                                
                                <!-- Dynamic Component Inputs -->
                                @foreach($components as $comp)
                                    @php
                                        $valRecord = $cieValues->get($comp->id);
                                    @endphp
                                    <td style="border-right: 1px solid var(--color-scsa-line);">
                                        @if($isEditable)
                                            <input type="number" step="0.01" min="0" max="{{ $comp->max_marks }}" 
                                                   name="comp_marks[{{ $student->id }}][{{ $comp->id }}]"
                                                   class="comp-input text-center" 
                                                   data-comp-id="{{ $comp->id }}"
                                                   data-max-marks="{{ $comp->max_marks }}"
                                                   style="width: 90px; text-align: center; padding: 0.45rem 0.5rem;"
                                                   value="{{ $valRecord ? $valRecord->marks_obtained : '' }}">
                                        @else
                                            <div class="text-center" style="font-weight: 700; color: var(--color-scsa-ink);">{{ $valRecord && $valRecord->marks_obtained !== null ? $valRecord->marks_obtained : '-' }}</div>
                                        @endif
                                    </td>
                                @endforeach
                                
                                <!-- CIE Total Display -->
                                <td style="background: rgba(0, 0, 0, 0.012); border-right: 1px solid var(--color-scsa-line);">
                                    <div class="cie-total text-center" style="font-weight: 700; color: var(--color-scsa-muted);">
                                        {{ $markRecord ? $markRecord->cie_30 : '0.00' }}
                                    </div>
                                </td>
                                
                                <!-- CIE Grand Total Display (50) -->
                                <td style="background: rgba(16, 185, 129, 0.04); {{ $isExternalEnabled ? 'border-right: 1px solid var(--color-scsa-line);' : '' }}">
                                    <div class="grand-total text-center" style="font-weight: 800; color: var(--color-scsa-success); font-family: var(--font-display); font-size: 1.05rem;">
                                        {{ $markRecord ? $markRecord->total_50 : '0.00' }}
                                    </div>
                                </td>

                                @if($isExternalEnabled)
                                    <!-- External Marks (50) -->
                                    <td style="border-right: 1px solid var(--color-scsa-line);">
                                        @if($isExternalEditable)
                                            <input type="number" step="0.01" min="0" max="50" 
                                                   name="external_marks[{{ $student->id }}]"
                                                   class="external-input text-center" 
                                                   style="width: 90px; text-align: center; padding: 0.45rem 0.5rem;"
                                                   value="{{ $markRecord ? $markRecord->external_50 : '' }}">
                                        @else
                                            <div class="text-center external-input-display" style="font-weight: 700; color: var(--color-scsa-ink);">{{ $markRecord && $markRecord->external_50 !== null ? $markRecord->external_50 : '-' }}</div>
                                        @endif
                                    </td>

                                    <!-- Total Marks (100) -->
                                    <td style="background: rgba(16, 185, 129, 0.08);">
                                        <div class="total-100 text-center" style="font-weight: 800; color: var(--color-scsa-success); font-family: var(--font-display); font-size: 1.05rem;">
                                            {{ $markRecord && $markRecord->total_100 !== null ? $markRecord->total_100 : '0.00' }}
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if($isEditable || $isExternalEditable)
                    <div class="actions" style="padding: 1.5rem; background: var(--bg-primary); border-top: 1px solid var(--color-scsa-line); display: flex; gap: 1rem; flex-wrap: wrap;">
                        @if($isEditable)
                            <button class="button" type="submit" name="action" value="save" formaction="{{ route('marks.store', $subjectAssignment) }}" style="padding: 0.65rem 1.5rem;">Save Internal Draft</button>
                            <button class="button" type="button" id="submit-btn" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success); padding: 0.65rem 1.5rem;">Submit to HOD</button>
                        @endif
                        @if($isExternalEditable)
                            <button class="button" type="submit" name="action" value="save" formaction="{{ route('marks.store-external', $subjectAssignment) }}" style="padding: 0.65rem 1.5rem;">Save External Draft</button>
                            <button class="button" type="button" id="submit-external-btn" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success); padding: 0.65rem 1.5rem;">Submit External Marks</button>
                        @endif
                    </div>
                @endif
            </form>
        @endif

        @if($isEditable)
            <!-- Separate Submit Form -->
            <form method="post" action="{{ route('marks.submit', $subjectAssignment) }}" id="submit-form" style="display: none;">
                @csrf
            </form>
        @endif
    </div>

    @if($showTabbedView)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tab toggling script
                const tabBtns = document.querySelectorAll('.tab-btn');
                const tabContents = document.querySelectorAll('.tab-content');
                
                tabBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Deactivate all tabs
                        tabBtns.forEach(b => {
                            b.classList.remove('active');
                            b.style.borderBottomColor = 'transparent';
                            b.style.color = 'var(--color-scsa-muted)';
                        });
                        
                        // Hide all content
                        tabContents.forEach(c => c.style.display = 'none');
                        
                        // Activate current tab
                        this.classList.add('active');
                        this.style.borderBottomColor = 'var(--color-scsa-accent)';
                        this.style.color = 'var(--color-scsa-accent)';
                        
                        // Show current content
                        const targetTab = this.getAttribute('data-tab');
                        document.getElementById(targetTab + '-section').style.display = 'block';
                    });
                });
            });
        </script>
    @endif

    @if($isEditable || $isExternalEditable)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('marks-form');
                if (!form) return;

                const submitBtn = document.getElementById('submit-btn');
                const submitForm = document.getElementById('submit-form');
                
                function calculateStudent(studentId) {
                    // Find both rows for this student (Internal table & External table)
                    const rows = document.querySelectorAll('.student-row[data-student-id="' + studentId + '"]');
                    if (rows.length === 0) return;

                    // 1. Get raw mid sem marks input
                    let scaledMidSem = 0.00;
                    const midSemInput = document.querySelector('input[name="mid_sem_30[' + studentId + ']"]');
                    if (midSemInput) {
                        const rawMidSem = parseFloat(midSemInput.value);
                        if (!isNaN(rawMidSem)) {
                            scaledMidSem = (rawMidSem / 30.0) * 20.0;
                        }
                    } else {
                        // If input not editable, read from static display
                        const scaledEls = document.querySelectorAll('.student-row[data-student-id="' + studentId + '"] .mid-sem-scaled');
                        if (scaledEls.length > 0) {
                            scaledMidSem = parseFloat(scaledEls[0].textContent) || 0.00;
                        }
                    }

                    // 2. Get CIE components
                    let cieTotal = 0.00;
                    const compInputs = document.querySelectorAll('.student-row[data-student-id="' + studentId + '"] .comp-input');
                    if (compInputs.length > 0) {
                        compInputs.forEach(input => {
                            const val = parseFloat(input.value);
                            if (!isNaN(val)) {
                                cieTotal += val;
                            }
                        });
                    } else {
                        const cieTotalEls = document.querySelectorAll('.student-row[data-student-id="' + studentId + '"] .cie-total');
                        if (cieTotalEls.length > 0) {
                            cieTotal = parseFloat(cieTotalEls[0].textContent) || 0.00;
                        }
                    }

                    // 3. CIE Total Marks (50) = scaledMidSem + cieTotal
                    const total50 = scaledMidSem + cieTotal;

                    // 4. Get External marks input
                    let externalVal = 0.00;
                    const externalInput = document.querySelector('input[name="external_marks[' + studentId + ']"]');
                    if (externalInput) {
                        const rawExternal = parseFloat(externalInput.value);
                        if (!isNaN(rawExternal)) {
                            externalVal = rawExternal;
                        }
                    } else {
                        const extEls = document.querySelectorAll('.student-row[data-student-id="' + studentId + '"] .external-input-display');
                        if (extEls.length > 0) {
                            externalVal = parseFloat(extEls[0].textContent) || 0.00;
                        }
                    }

                    // 5. Total Marks (100) = total50 + externalVal
                    const total100 = total50 + externalVal;

                    // Now update all display elements across all rows for this student
                    rows.forEach(row => {
                        const scaledEl = row.querySelector('.mid-sem-scaled');
                        if (scaledEl) scaledEl.textContent = scaledMidSem.toFixed(2);

                        const cieTotalEl = row.querySelector('.cie-total');
                        if (cieTotalEl) cieTotalEl.textContent = cieTotal.toFixed(2);

                        const grandTotalEl = row.querySelector('.grand-total');
                        if (grandTotalEl) grandTotalEl.textContent = total50.toFixed(2);

                        const total100El = row.querySelector('.total-100');
                        if (total100El) total100El.textContent = total100.toFixed(2);
                    });
                }

                // Attach event listeners for real-time calculations
                form.addEventListener('input', function(e) {
                    if (e.target.classList.contains('mid-sem-input') || e.target.classList.contains('comp-input') || e.target.classList.contains('external-input')) {
                        const row = e.target.closest('.student-row');
                        if (row) {
                            const studentId = row.getAttribute('data-student-id');
                            calculateStudent(studentId);
                        }
                    }
                });

                // Validation before draft saving or submissions
                form.addEventListener('submit', function(e) {
                    const inputs = form.querySelectorAll('input[type="number"]');
                    let valid = true;
                    inputs.forEach(input => {
                        const val = parseFloat(input.value);
                        const max = parseFloat(input.getAttribute('max'));
                        const min = parseFloat(input.getAttribute('min'));
                        
                        if (!isNaN(val)) {
                            if (val < min || val > max) {
                                input.style.borderColor = 'var(--color-scsa-danger, #ef4444)';
                                valid = false;
                            } else {
                                input.style.borderColor = '';
                            }
                        }
                    });

                    if (!valid) {
                        e.preventDefault();
                        alert('Please correct errors. Some marks entered exceed the maximum limit permitted.');
                    }
                });

                // Final submit triggers confirmation and submit-form post
                if (submitBtn && submitForm) {
                    submitBtn.addEventListener('click', function() {
                        const confirmSubmit = confirm(
                            "Are you absolutely sure you want to submit these marks to the HOD?\n\n" +
                            "This action will LOCK the gradesheet for you. You will not be able to edit it unless your HOD unlocks it."
                        );
                        
                        if (confirmSubmit) {
                            submitForm.submit();
                        }
                    });
                }

                const submitExternalBtn = document.getElementById('submit-external-btn');
                if (submitExternalBtn) {
                    submitExternalBtn.addEventListener('click', function() {
                        const confirmSubmit = confirm(
                            "Are you absolutely sure you want to submit these external marks?\n\n" +
                            "This action will LOCK the external marks. You will not be able to edit them further."
                        );
                        
                        if (confirmSubmit) {
                            form.action = "{{ route('marks.submit-external', $subjectAssignment) }}";
                            form.submit();
                        }
                    });
                }
            });
        </script>
    @endif
@endsection
