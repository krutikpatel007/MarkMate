@extends('layouts.app')

@section('title', 'Bulk Upload Students | SCSA Attendance')
@section('page-title', 'Bulk upload students')
@section('page-subtitle', 'Import a full class list from CSV or XLSX in one step')

@section('page-actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('academics.students.import.template') }}">Download template</a>
        <a class="button secondary" href="{{ route('academics.students.index', array_filter(['class_section_id' => $selectedSectionId])) }}">Back to Students</a>
    </div>
@endsection

@section('content')
    <div class="grid grid-2">
        <form class="card" method="post" action="{{ route('academics.students.import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="field">
                <label for="class_section_id">Class section</label>
                <select id="class_section_id" name="class_section_id" required>
                    <option value="">Select class</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" @selected((int) old('class_section_id', $selectedSectionId) === (int) $section->id)>
                            {{ $section->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="student_file">Student file</label>
                <input id="student_file" name="student_file" type="file" accept=".csv,.xlsx" required>
                <div class="muted">Supported formats: `.csv` and `.xlsx`.</div>
            </div>

            <div class="actions">
                <button class="button" type="submit">Import students</button>
                <a class="button secondary" href="{{ route('academics.students.index', array_filter(['class_section_id' => $selectedSectionId])) }}">Cancel</a>
            </div>
        </form>

        <section class="card">
            <h2>File format</h2>
            <table>
                <thead>
                <tr>
                    <th>Column</th>
                    <th>Required</th>
                    <th>Notes</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>`name`</td>
                    <td>Yes</td>
                    <td>Student full name</td>
                </tr>
                <tr>
                    <td>`enrollment_no`</td>
                    <td>Yes</td>
                    <td>Must be unique</td>
                </tr>
                <tr>
                    <td>`roll_no`</td>
                    <td>No</td>
                    <td>Class roll number</td>
                </tr>
                <tr>
                    <td>`username`</td>
                    <td>No</td>
                    <td>If blank, enrollment number is used</td>
                </tr>
                <tr>
                    <td>`email`</td>
                    <td>Yes</td>
                    <td>Required and must be unique</td>
                </tr>
                <tr>
                    <td>`mobile`</td>
                    <td>Yes</td>
                    <td>Required student contact number</td>
                </tr>
                <tr>
                    <td>`password`</td>
                    <td>No</td>
                    <td>If blank, `student123` is used and the student must change it on first login</td>
                </tr>
                </tbody>
            </table>
        </section>
    </div>
@endsection
