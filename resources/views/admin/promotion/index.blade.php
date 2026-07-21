@extends('layouts.app')

@section('title', 'Semester Promotion | Admin Panel')
@section('page-title', 'Semester Promotion Wizard')
@section('page-subtitle', 'Batch-promote student sections to the next academic semester or mark them as graduated')

@section('content')
    @if(session('success'))
        <div style="background-color: var(--color-scsa-success-light, #ecfdf5); color: var(--color-scsa-success, #059669); border: 1px solid var(--color-scsa-success, #059669); padding: 1rem; border-radius: var(--border-radius-md); margin-bottom: 1.5rem; font-weight: 500;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background-color: #fef2f2; color: var(--color-scsa-danger, #dc2626); border: 1px solid var(--color-scsa-danger, #dc2626); padding: 1rem; border-radius: var(--border-radius-md); margin-bottom: 1.5rem; font-weight: 500;">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-3" style="align-items: start; gap: 1.5rem;">
        <!-- Wizard Panel -->
        <section class="card" style="grid-column: span 2;">
            <h2>Configure Student Promotion</h2>
            <p class="muted" style="margin-bottom: 1.5rem;">Select the source class section, choose the action (promote or graduate), and verify student details before initiating.</p>
            
            <form method="post" action="{{ route('admin.promotion.promote') }}" onsubmit="return confirm('CRITICAL ACTION: Are you sure you want to promote/graduate these students? This updates core student records in bulk.');">
                @csrf
                
                <!-- 1. Source Section -->
                <div class="field" style="margin-bottom: 1.25rem;">
                    <label for="source_class_section_id" style="font-weight: 700;">1. Select Source Class Section</label>
                    <select id="source_class_section_id" name="source_class_section_id" required>
                        <option value="">Select a class to promote...</option>
                        @foreach($classSections as $cs)
                            <option value="{{ $cs->id }}" data-program-id="{{ $cs->program_id }}" data-program-code="{{ $cs->program->program_code }}" data-students="{{ $cs->students_count }}">
                                {{ $cs->display_name }} ({{ $cs->program->program_name }}) — {{ $cs->students_count }} active students
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Action Selector -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="font-weight: 700; display: block; margin-bottom: 0.5rem;">2. Choose Action Type</label>
                    <div style="display: flex; gap: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                            <input type="radio" name="action_type" value="promote" checked id="action_promote">
                            Promote to Higher Class
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer; color: var(--color-scsa-danger);">
                            <input type="radio" name="action_type" value="graduate" id="action_graduate">
                            Graduate / Inactivate Section
                        </label>
                    </div>
                </div>

                <!-- 3. Target Section -->
                <div id="target_class_container" class="field" style="margin-bottom: 1.5rem;">
                    <label for="target_class_section_id" style="font-weight: 700;">3. Select Target Class Section</label>
                    <select id="target_class_section_id" name="target_class_section_id">
                        <option value="">Select target class section...</option>
                        @foreach($classSections as $cs)
                            <option value="{{ $cs->id }}" data-program-id="{{ $cs->program_id }}">
                                {{ $cs->display_name }} (Sem {{ $cs->semester->semester_no }})
                            </option>
                        @endforeach
                    </select>
                    <p class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Only classes belonging to the same academic program are selectable.</p>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="button" style="width: 100%; min-height: 44px; font-weight: 700;">
                        🎓 Execute Transition
                    </button>
                </div>
            </form>
        </section>

        <!-- Sidebar Section Stats -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Instructions and Validation Rules -->
            <section class="card">
                <h2>Promotion Rules</h2>
                <div style="font-size: 0.85rem; line-height: 1.5; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="border-left: 3px solid var(--color-scsa-accent); padding-left: 0.75rem;">
                        <strong>Same Program constraint</strong>: Students can only be promoted to target classes within their same program (e.g. BCA to BCA).
                    </div>
                    <div style="border-left: 3px solid var(--color-scsa-gold); padding-left: 0.75rem;">
                        <strong>Historical Attendance</strong>: Attendance logs generated in the old semester remain archived and linked to the historical sessions. Only the student's active reference updates.
                    </div>
                    <div style="border-left: 3px solid var(--color-scsa-danger); padding-left: 0.75rem;">
                        <strong>Final Semesters</strong>: Use the <strong>Graduate</strong> action for students completing their final semester (e.g. 6BCA) to deactivate their profiles in bulk.
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sourceSelect = document.getElementById('source_class_section_id');
            const targetSelect = document.getElementById('target_class_section_id');
            const targetContainer = document.getElementById('target_class_container');
            const actionPromote = document.getElementById('action_promote');
            const actionGraduate = document.getElementById('action_graduate');

            const allTargetOptions = Array.from(targetSelect.options);

            function updateTargetOptions() {
                const selectedSource = sourceSelect.options[sourceSelect.selectedIndex];
                if (!selectedSource || sourceSelect.value === "") {
                    // Reset target
                    targetSelect.value = "";
                    return;
                }

                const programId = selectedSource.dataset.programId;
                const sourceId = sourceSelect.value;

                // Show only options matching programId and NOT matching sourceId
                targetSelect.innerHTML = '';
                
                // Add placeholder
                const placeholder = document.createElement('option');
                placeholder.value = "";
                placeholder.textContent = "Select target class section...";
                targetSelect.appendChild(placeholder);

                allTargetOptions.forEach(opt => {
                    if (opt.value === "") return;
                    if (opt.dataset.programId === programId && opt.value !== sourceId) {
                        const newOpt = opt.cloneNode(true);
                        targetSelect.appendChild(newOpt);
                    }
                });
            }

            sourceSelect.addEventListener('change', updateTargetOptions);

            function toggleActionType() {
                if (actionGraduate.checked) {
                    targetContainer.style.display = 'none';
                    targetSelect.removeAttribute('required');
                } else {
                    targetContainer.style.display = 'block';
                    targetSelect.setAttribute('required', 'required');
                }
            }

            actionPromote.addEventListener('change', toggleActionType);
            actionGraduate.addEventListener('change', toggleActionType);
            
            // Set initial state
            toggleActionType();
        });
    </script>
@endsection
