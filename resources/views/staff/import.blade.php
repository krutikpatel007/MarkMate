@extends('layouts.app')

@section('title', 'Bulk Upload Staff Users | SCSA Attendance')
@section('page-title', 'Bulk upload staff')
@section('page-subtitle', 'Import multiple HODs and Faculty members in one step')

@section('page-actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('staff.import.template') }}">Download template</a>
        <a class="button secondary" href="{{ route('staff.index') }}">Back to Staff</a>
    </div>
@endsection

@section('content')
    <div class="grid grid-2">
        <form class="card" method="post" action="{{ route('staff.import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="field">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" required>
                    <option value="">Select department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected((int) old('department_id', $selectedDepartmentId) === (int) $dept->id)>
                            {{ $dept->department_code }} — {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="staff_file">Staff file</label>
                <input id="staff_file" name="staff_file" type="file" accept=".csv,.xlsx" required>
                <div class="muted">Supported formats: `.csv` and `.xlsx`.</div>
            </div>

            <div class="actions">
                <button class="button" type="submit">Import staff</button>
                <a class="button secondary" href="{{ route('staff.index') }}">Cancel</a>
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
                    <td>Staff full name</td>
                </tr>
                <tr>
                    <td>`employee_code`</td>
                    <td>Yes</td>
                    <td>Unique staff employee identifier</td>
                </tr>
                <tr>
                    <td>`username`</td>
                    <td>No</td>
                    <td>Unique login ID. If blank, employee code is used</td>
                </tr>
                <tr>
                    <td>`email`</td>
                    <td>No</td>
                    <td>Must be unique</td>
                </tr>
                <tr>
                    <td>`role`</td>
                    <td>Yes</td>
                    <td>Must be either `hod` or `faculty`</td>
                </tr>
                <tr>
                    <td>`designation`</td>
                    <td>No</td>
                    <td>E.g. Professor, Assistant Professor</td>
                </tr>
                <tr>
                    <td>`display_initials`</td>
                    <td>No</td>
                    <td>Short initials for timetable display</td>
                </tr>
                <tr>
                    <td>`password`</td>
                    <td>No</td>
                    <td>If blank, `staff123` is used</td>
                </tr>
                </tbody>
            </table>
        </section>
    </div>
@endsection
