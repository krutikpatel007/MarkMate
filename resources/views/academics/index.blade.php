@extends('layouts.app')

@section('title', 'Academic Management | SCSA Attendance')
@section('page-title', 'Classes, subjects & students')
@section('page-subtitle', 'Add, rename, or remove class sections, subjects, and student records (admin / HOD)')

@section('content')
    <div class="grid grid-2" data-motion="stagger-cards">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('departments.index') }}" class="card" style="text-decoration: none; color: inherit;">
            <h2>Departments</h2>
            <p class="muted">Create and manage university departments. Each department contains programs, faculty, and students.</p>
        </a>
        @endif
        <a href="{{ route('programs.index') }}" class="card" style="text-decoration: none; color: inherit;">
            <h2>Programs</h2>
            <p class="muted">Manage degree programs (BCA, MCA, BSc, etc.) and their semester structures under each department.</p>
        </a>
        <a href="{{ route('academics.classes.index') }}" class="card" style="text-decoration: none; color: inherit;">
            <h2>Class sections</h2>
            <p class="muted">Create batches (e.g. BCA Sem 1 A), rename display names, or remove empty classes.</p>
        </a>
        <a href="{{ route('academics.subjects.index') }}" class="card" style="text-decoration: none; color: inherit;">
            <h2>Subjects</h2>
            <p class="muted">Add subject codes and titles per program semester, or rename and retire subjects.</p>
        </a>
        <a href="{{ route('academics.students.index') }}" class="card" style="text-decoration: none; color: inherit;">
            <h2>Students</h2>
            <p class="muted">Enroll students one by one or bulk upload a full class from CSV / XLSX, then update or deactivate accounts.</p>
        </a>
    </div>
@endsection
