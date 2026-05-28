@extends('layouts.app')

@section('title', 'Configure Evaluation Components | SCSA Attendance')
@section('page-title', 'Configure CIE Components')
@section('page-subtitle')
    {{ $subjectAssignment->subject->subject_name }} | {{ $subjectAssignment->classSection->display_name }}
@endsection

@section('content')
    <div class="card" style="max-width: 52rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-md); padding: 2rem;">
        <!-- Premium Step Info Banner -->
        <div style="background: var(--color-scsa-accent-soft); border-radius: var(--border-radius-lg); padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center; border-left: 4px solid var(--color-scsa-accent);">
            <div style="font-size: 1.75rem; line-height: 1;">⚙️</div>
            <div style="font-size: 0.875rem; line-height: 1.5; color: var(--color-scsa-ink); font-weight: 500;">
                <strong style="display: block; font-size: 0.9375rem; margin-bottom: 0.15rem; font-family: var(--font-display);">Define Continuous Internal Evaluation (CIE) Columns</strong>
                Configure customized assessment components (e.g. Assignments, Quizzes, Projects, Labs).
                The only rule is that the sum of the **Max Marks** of all components must equal **exactly 30**.
            </div>
        </div>

        <form method="post" action="{{ route('marks.configure.store', $subjectAssignment) }}" id="config-form">
            @csrf

            <div id="components-container">
                @if($components->isEmpty())
                    <!-- Default fields for new configuration -->
                    <div class="component-row grid grid-3" style="align-items: flex-end; gap: 1.25rem; margin-bottom: 1.25rem; padding: 1rem; background: rgba(0,0,0,0.015); border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line); transition: all 0.2s ease;">
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Component Name</label>
                            <input name="components[0][name]" required placeholder="e.g. Assignment 1" value="Assignment 1" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Max Marks</label>
                            <input name="components[0][max_marks]" type="number" required min="1" max="30" class="max-marks-input" placeholder="e.g. 10" value="10" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div>
                            <button type="button" class="button danger remove-btn" disabled style="min-height: unset; padding: 0.6rem 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🗑️ Remove
                            </button>
                        </div>
                    </div>
                    <div class="component-row grid grid-3" style="align-items: flex-end; gap: 1.25rem; margin-bottom: 1.25rem; padding: 1rem; background: rgba(0,0,0,0.015); border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line); transition: all 0.2s ease;">
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Component Name</label>
                            <input name="components[1][name]" required placeholder="e.g. Class Test" value="Class Test" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Max Marks</label>
                            <input name="components[1][max_marks]" type="number" required min="1" max="30" class="max-marks-input" placeholder="e.g. 10" value="10" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div>
                            <button type="button" class="button danger remove-btn" disabled style="min-height: unset; padding: 0.6rem 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🗑️ Remove
                            </button>
                        </div>
                    </div>
                    <div class="component-row grid grid-3" style="align-items: flex-end; gap: 1.25rem; margin-bottom: 1.25rem; padding: 1rem; background: rgba(0,0,0,0.015); border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line); transition: all 0.2s ease;">
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Component Name</label>
                            <input name="components[2][name]" required placeholder="e.g. Presentation" value="Presentation" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Max Marks</label>
                            <input name="components[2][max_marks]" type="number" required min="1" max="30" class="max-marks-input" placeholder="e.g. 5" value="5" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div>
                            <button type="button" class="button danger remove-btn" disabled style="min-height: unset; padding: 0.6rem 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🗑️ Remove
                            </button>
                        </div>
                    </div>
                    <div class="component-row grid grid-3" style="align-items: flex-end; gap: 1.25rem; margin-bottom: 1.25rem; padding: 1rem; background: rgba(0,0,0,0.015); border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line); transition: all 0.2s ease;">
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Component Name</label>
                            <input name="components[3][name]" required placeholder="e.g. Attendance" value="Attendance" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div class="field" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Max Marks</label>
                            <input name="components[3][max_marks]" type="number" required min="1" max="30" class="max-marks-input" placeholder="e.g. 5" value="5" style="padding: 0.55rem 0.75rem;">
                        </div>
                        <div>
                            <button type="button" class="button danger remove-btn" disabled style="min-height: unset; padding: 0.6rem 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🗑️ Remove
                            </button>
                        </div>
                    </div>
                @else
                    @foreach($components as $index => $comp)
                        <div class="component-row grid grid-3" style="align-items: flex-end; gap: 1.25rem; margin-bottom: 1.25rem; padding: 1rem; background: rgba(0,0,0,0.015); border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line); transition: all 0.2s ease;">
                            <div class="field" style="margin-bottom: 0;">
                                <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Component Name</label>
                                <input name="components[{{ $index }}][name]" required value="{{ $comp->name }}" style="padding: 0.55rem 0.75rem;">
                            </div>
                            <div class="field" style="margin-bottom: 0;">
                                <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Max Marks</label>
                                <input name="components[{{ $index }}][max_marks]" type="number" required min="1" max="30" class="max-marks-input" value="{{ (int)$comp->max_marks }}" style="padding: 0.55rem 0.75rem;">
                            </div>
                            <div>
                                <button type="button" class="button danger remove-btn" style="min-height: unset; padding: 0.6rem 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                    🗑️ Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div style="margin-bottom: 1.75rem;">
                <button type="button" class="button secondary" id="add-component-btn" style="padding: 0.55rem 1.125rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                    ➕ Add Column
                </button>
            </div>

            <!-- Premium Interactive Calculator Panel -->
            <div id="sum-alert" style="border-radius: var(--border-radius-lg); padding: 1.25rem; font-size: 0.95rem; font-weight: 700; display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border: 1px solid var(--color-scsa-line); transition: all 0.25s ease; box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem; line-height: 1;">📊</span>
                    <span style="color: var(--color-scsa-ink);">Continuous Evaluation Sum: <strong id="total-sum" style="font-size: 1.35rem; font-family: var(--font-display);">0</strong> <span style="font-weight: 500; opacity: 0.8; font-size: 0.9rem;">/ 30 Marks</span></span>
                </div>
                <span id="sum-validation-msg" class="badge" style="padding: 0.45rem 1rem; font-size: 0.75rem; font-weight: 800; border-radius: 99px; transition: all 0.2s ease;"></span>
            </div>

            <div class="actions" style="border-top: 1px solid var(--color-scsa-line); padding-top: 1.5rem; display: flex; gap: 1rem;">
                <button class="button" type="submit" id="save-config-btn" style="padding: 0.65rem 1.5rem; font-size: 0.9375rem;">Save Components</button>
                <a class="button secondary" href="{{ route('marks.index') }}" style="padding: 0.65rem 1.5rem; font-size: 0.9375rem;">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('components-container');
            const addBtn = document.getElementById('add-component-btn');
            const totalSumEl = document.getElementById('total-sum');
            const alertEl = document.getElementById('sum-alert');
            const validationMsg = document.getElementById('sum-validation-msg');
            const saveBtn = document.getElementById('save-config-btn');
            
            let componentIndex = container.querySelectorAll('.component-row').length;

            function updateSum() {
                const inputs = container.querySelectorAll('.max-marks-input');
                let sum = 0;
                inputs.forEach(input => {
                    sum += parseFloat(input.value) || 0;
                });

                totalSumEl.textContent = sum;

                if (sum === 30) {
                    alertEl.style.background = 'rgba(16, 185, 129, 0.08)';
                    alertEl.style.borderColor = '#10b981';
                    validationMsg.style.background = 'rgba(16, 185, 129, 0.15)';
                    validationMsg.style.color = '#10b981';
                    validationMsg.textContent = '✓ READY TO SAVE';
                    saveBtn.removeAttribute('disabled');
                } else {
                    alertEl.style.background = 'rgba(245, 158, 11, 0.08)';
                    alertEl.style.borderColor = '#f59e0b';
                    validationMsg.style.background = 'rgba(245, 158, 11, 0.15)';
                    validationMsg.style.color = '#f59e0b';
                    validationMsg.textContent = sum > 30 ? '⚠ EXCEEDS 30' : '⚠ MUST EQUAL 30';
                    saveBtn.setAttribute('disabled', 'true');
                }

                // Enable/disable remove buttons
                const rows = container.querySelectorAll('.component-row');
                rows.forEach(row => {
                    const removeBtn = row.querySelector('.remove-btn');
                    if (rows.length <= 1) {
                        removeBtn.setAttribute('disabled', 'true');
                    } else {
                        removeBtn.removeAttribute('disabled');
                    }
                });
            }

            container.addEventListener('input', function(e) {
                if (e.target.classList.contains('max-marks-input')) {
                    updateSum();
                }
            });

            container.addEventListener('click', function(e) {
                if (e.target.closest('.remove-btn')) {
                    e.target.closest('.component-row').remove();
                    updateSum();
                }
            });

            addBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'component-row grid grid-3';
                newRow.style.alignItems = 'flex-end';
                newRow.style.gap = '1.25rem';
                newRow.style.marginBottom = '1.25rem';
                newRow.style.padding = '1rem';
                newRow.style.background = 'rgba(0,0,0,0.015)';
                newRow.style.borderRadius = 'var(--border-radius-lg)';
                newRow.style.border = '1px solid var(--color-scsa-line)';
                newRow.style.transition = 'all 0.2s ease';
                
                newRow.innerHTML = `
                    <div class="field" style="margin-bottom: 0;">
                        <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Component Name</label>
                        <input name="components[${componentIndex}][name]" required placeholder="e.g. Quiz" style="padding: 0.55rem 0.75rem;">
                    </div>
                    <div class="field" style="margin-bottom: 0;">
                        <label style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">Max Marks</label>
                        <input name="components[${componentIndex}][max_marks]" type="number" required min="1" max="30" class="max-marks-input" placeholder="e.g. 5" value="5" style="padding: 0.55rem 0.75rem;">
                    </div>
                    <div>
                        <button type="button" class="button danger remove-btn" style="min-height: unset; padding: 0.6rem 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                            🗑️ Remove
                        </button>
                    </div>
                `;
                
                container.appendChild(newRow);
                componentIndex++;
                updateSum();
            });

            updateSum();
        });
    </script>
@endsection
