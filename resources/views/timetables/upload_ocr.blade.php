@extends('layouts.app')

@section('title', 'AI Timetable Uploader | SCSA Attendance')
@section('page-title', 'AI Timetable Uploader')
@section('page-subtitle', 'Upload a timetable image/screenshot and let AI automatically detect and map the lecture slots.')

@section('page-actions')
    <a class="button secondary" href="{{ route('timetables.slots') }}">Back to Slots</a>
@endsection

@section('content')
    <div class="card" style="max-width: 45rem;">
        @if($sections->isEmpty())
            <p class="muted">You are not authorized or there are no active class sections. Please configure class sections first.</p>
        @else
            <form method="post" action="{{ route('timetables.process-ocr') }}" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label for="class_section_id">Target Class Section</label>
                    <select id="class_section_id" name="class_section_id" required>
                        <option value="">Select Class Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @selected(old('class_section_id') == $section->id)>
                                {{ $section->display_name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="muted" style="margin-top: 0.25rem; font-size: 0.75rem;">
                        Timetable slots will be generated and mapped to active subject assignments of this class section.
                    </div>
                </div>

                <div class="field" style="margin-top: 1.5rem;">
                    <label for="timetable_image">Timetable Screenshot / Image</label>
                    <div style="border: 2px dashed #cbd5e1; border-radius: 0.5rem; padding: 2rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s;" 
                         onclick="document.getElementById('timetable_image').click()"
                         onmouseover="this.style.borderColor='var(--color-scsa-accent)'; this.style.background='#f0fdfa';"
                         onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                        
                        <svg style="width: 3rem; height: 3rem; color: #94a3b8; margin: 0 auto 0.75rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span id="file-label-text" style="font-weight: 600; color: #475569; font-size: 0.875rem;">Click to select or drag &amp; drop timetable screenshot</span>
                        <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Supports PNG, JPG, JPEG (Max 10MB)</div>
                    </div>
                    <input id="timetable_image" name="timetable_image" type="file" accept="image/png, image/jpeg, image/jpg" required style="display: none;" onchange="updateFileLabel(this)">
                </div>

                @if($errors->any())
                    <div class="alert error" style="margin-top: 1.5rem; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.8125rem;">
                        @foreach($errors->all() as $error)
                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                <svg style="width: 1rem; height: 1rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span>{{ $error }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="actions" style="margin-top: 2rem;">
                    <button class="button" type="submit">Process Image &amp; Preview</button>
                    <a class="button secondary" href="{{ route('timetables.index') }}">Cancel</a>
                </div>
            </form>
        @endif
    </div>

    <script>
        function updateFileLabel(input) {
            const labelText = document.getElementById('file-label-text');
            if (input.files && input.files[0]) {
                labelText.textContent = 'Selected: ' + input.files[0].name;
                labelText.style.color = 'var(--color-scsa-accent-deep)';
            } else {
                labelText.textContent = 'Click to select or drag & drop timetable screenshot';
                labelText.style.color = '#475569';
            }
        }
    </script>
@endsection
