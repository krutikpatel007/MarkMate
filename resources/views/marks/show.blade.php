@extends('layouts.app')

@section('title', 'Enter Internal Marks | SCSA Attendance')
@section('page-title', 'Internal Marks Sheet')
@section('page-subtitle')
    {{ $subjectAssignment->subject->subject_code }} — {{ $subjectAssignment->subject->subject_name }}
    | {{ $subjectAssignment->classSection->display_name }}
@endsection

@section('page-actions')
    <div class="actions" style="display: flex; gap: 0.5rem; align-items: center;">
        @php
            $user = auth()->user();
            $isExamDept = $user->facultyProfile?->department?->department_code === 'EXAM';
            $showUnlock = false;
            
            if ($user->isAdmin()) {
                $showUnlock = ($status === 'submitted_to_hod' || $status === 'submitted_to_exam' || $status === 'submitted');
            } elseif ($user->isHod()) {
                if ($isExamDept) {
                    $showUnlock = ($status === 'submitted_to_exam');
                } else {
                    $showUnlock = ($status === 'submitted_to_hod');
                }
            }
        @endphp
        
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

    <div class="card" style="padding: 0; overflow: hidden; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-md);">
        <form method="post" action="{{ route('marks.store', $subjectAssignment) }}" id="marks-form">
            @csrf

            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; max-height: 32rem;">
                <table class="marks-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead style="position: sticky; top: 0; z-index: 5;">
                    <tr>
                        <th style="width: 110px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Roll No.</th>
                        <th style="min-width: 250px; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Student Name</th>
                        
                        <!-- Mid Sem Raw Header -->
                        <th style="width: 130px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);">Mid Sem<br>Marks (30)</th>
                        <!-- Mid Sem Scaled Header -->
                        <th style="width: 130px; text-align: center; background: linear-gradient(rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.02)), var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line); color: var(--color-scsa-muted);">Mid Sem<br>Scaled (20)</th>
                        
                        <!-- Dynamic Component Headers -->
                        @foreach($components as $comp)
                            <th style="width: 135px; text-align: center; background: var(--bg-primary); border-bottom: 2px solid var(--color-scsa-line); border-right: 1px solid var(--color-scsa-line);" data-comp-id="{{ $comp->id }}" data-max-marks="{{ $comp->max_marks }}">
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
                            <td style="border-right: 1px solid var(--color-scsa-line); padding: 0.75rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <!-- Initials Avatar -->
                                    <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: linear-gradient(135deg, var(--color-scsa-accent-soft) 0%, rgba(13, 148, 136, 0.05) 100%); border: 1.5px solid var(--color-scsa-line); display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; font-weight: 700; color: var(--color-scsa-accent); flex-shrink: 0;">
                                        {{ strtoupper(substr($student->user->name, 0, 1)) }}{{ strtoupper(substr(strrchr($student->user->name, ' ') ?: ' ', 1, 1)) }}
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 700; color: var(--color-scsa-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: var(--font-display);">{{ $student->user->name }}</div>
                                        <div class="muted" style="font-size: 0.725rem;">{{ $student->enrollment_no }}</div>
                                    </div>
                                </div>
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
                            
                            <!-- Grand Total Display -->
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
        </form>

        @if($isEditable)
            <!-- Separate Submit Form -->
            <form method="post" action="{{ route('marks.submit', $subjectAssignment) }}" id="submit-form" style="display: none;">
                @csrf
            </form>
        @endif
    </div>

    @if($isEditable)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('marks-form');
                const submitBtn = document.getElementById('submit-btn');
                const submitForm = document.getElementById('submit-form');
                
                function calculateRow(row) {
                    // 1. Calculate Mid Sem scaled (raw / 30 * 20)
                    const midSemInput = row.querySelector('.mid-sem-input');
                    const rawMidSem = parseFloat(midSemInput.value);
                    let scaledMidSem = 0.00;
                    
                    if (!isNaN(rawMidSem)) {
                        scaledMidSem = (rawMidSem / 30.0) * 20.0;
                        row.querySelector('.mid-sem-scaled').textContent = scaledMidSem.toFixed(2);
                    } else {
                        row.querySelector('.mid-sem-scaled').textContent = '0.00';
                    }

                    // 2. Calculate CIE components sum
                    const compInputs = row.querySelectorAll('.comp-input');
                    let cieTotal = 0.00;
                    compInputs.forEach(input => {
                        const val = parseFloat(input.value);
                        if (!isNaN(val)) {
                            cieTotal += val;
                        }
                    });
                    row.querySelector('.cie-total').textContent = cieTotal.toFixed(2);

                    // 3. Grand Total (CIE + Mid Sem Scaled)
                    const grandTotal = scaledMidSem + cieTotal;
                    row.querySelector('.grand-total').textContent = grandTotal.toFixed(2);
                }

                // Attach event listeners for real-time calculations
                form.addEventListener('input', function(e) {
                    if (e.target.classList.contains('mid-sem-input') || e.target.classList.contains('comp-input')) {
                        const row = e.target.closest('.student-row');
                        calculateRow(row);
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
                if (submitBtn) {
                    submitBtn.addEventListener('click', function() {
                        const confirmSubmit = confirm(
                            "Are you absolutely sure you want to submit these marks to the HOD?\n\n" +
                            "This action will LOCK the gradesheet for you. You will not be able to edit it unless your HOD unlocks it."
                        );
                        
                        if (confirmSubmit) {
                            // Save draft first via AJAX or ensure we submit the locked submit-form
                            // An extremely robust approach is to first submit the draft form asynchronously, 
                            // then submit the lock form, OR simple double post!
                            // Let's perform a direct submit of the submitForm:
                            submitForm.submit();
                        }
                    });
                }
            });
        </script>
    @endif
@endsection
