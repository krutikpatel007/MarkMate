<?php

use App\Http\Controllers\AcademicManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\AttendanceMonitorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExtraLectureRequestController;
use App\Http\Controllers\FacultyAssignmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffUserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\StudentLeaveController;
use App\Http\Controllers\FacultyLeaveController;
use App\Http\Controllers\InternalMarkController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\DefaulterWarningController;
use App\Http\Controllers\AttendanceHeatmapController;
use App\Http\Controllers\ExamHallTicketController;
use App\Http\Controllers\ReEvaluationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/change', [AuthController::class, 'showPasswordChange'])->name('password.edit');
    Route::put('/password/change', [AuthController::class, 'updatePassword'])->name('password.update');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/setup', [DashboardController::class, 'setup'])->name('setup.index');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
    Route::get('/programs/create', [ProgramController::class, 'create'])->name('programs.create');
    Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
    Route::get('/programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
    Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');

    Route::get('/academics', [AcademicManagementController::class, 'index'])->name('academics.index');
    Route::get('/academics/classes', [ClassSectionController::class, 'index'])->name('academics.classes.index');
    Route::get('/academics/classes/create', [ClassSectionController::class, 'create'])->name('academics.classes.create');
    Route::post('/academics/classes', [ClassSectionController::class, 'store'])->name('academics.classes.store');
    Route::get('/academics/classes/{classSection}/edit', [ClassSectionController::class, 'edit'])->name('academics.classes.edit');
    Route::put('/academics/classes/{classSection}', [ClassSectionController::class, 'update'])->name('academics.classes.update');
    Route::delete('/academics/classes/{classSection}', [ClassSectionController::class, 'destroy'])->name('academics.classes.destroy');
    Route::get('/academics/subjects', [SubjectController::class, 'index'])->name('academics.subjects.index');
    Route::get('/academics/subjects/create', [SubjectController::class, 'create'])->name('academics.subjects.create');
    Route::post('/academics/subjects', [SubjectController::class, 'store'])->name('academics.subjects.store');
    Route::get('/academics/subjects/{subject}/edit', [SubjectController::class, 'edit'])->name('academics.subjects.edit');
    Route::put('/academics/subjects/{subject}', [SubjectController::class, 'update'])->name('academics.subjects.update');
    Route::delete('/academics/subjects/{subject}', [SubjectController::class, 'destroy'])->name('academics.subjects.destroy');
    Route::get('/academics/students', [StudentController::class, 'index'])->name('academics.students.index');
    Route::get('/academics/students/create', [StudentController::class, 'create'])->name('academics.students.create');
    Route::get('/academics/students/import', [StudentController::class, 'importCreate'])->name('academics.students.import.create');
    Route::get('/academics/students/import/template', [StudentController::class, 'importTemplate'])->name('academics.students.import.template');
    Route::post('/academics/students/import', [StudentController::class, 'importStore'])->name('academics.students.import.store');
    Route::post('/academics/students', [StudentController::class, 'store'])->name('academics.students.store');
    Route::get('/academics/students/{student}/edit', [StudentController::class, 'edit'])->name('academics.students.edit');
    Route::put('/academics/students/{student}', [StudentController::class, 'update'])->name('academics.students.update');
    Route::delete('/academics/students/{student}', [StudentController::class, 'destroy'])->name('academics.students.destroy');

    Route::get('/attendance/{lectureSession}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{lectureSession}', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/{lectureSession}/correction-requests', [AttendanceCorrectionRequestController::class, 'store'])
        ->name('attendance.correction-requests.store');
    Route::get('/attendance-corrections', [AttendanceCorrectionRequestController::class, 'index'])->name('attendance-corrections.index');
    Route::patch('/attendance-corrections/{correctionRequest}/decision', [AttendanceCorrectionRequestController::class, 'decide'])
        ->name('attendance-corrections.decide');
    Route::get('/attendance-monitor', [AttendanceMonitorController::class, 'index'])->name('attendance.monitor');
    Route::patch('/attendance-monitor/{lectureSession}/status', [AttendanceMonitorController::class, 'updateStatus'])
        ->name('attendance.monitor.status');

    Route::get('/staff/import', [StaffUserController::class, 'importCreate'])->name('staff.import.create');
    Route::get('/staff/import/template', [StaffUserController::class, 'importTemplate'])->name('staff.import.template');
    Route::post('/staff/import', [StaffUserController::class, 'importStore'])->name('staff.import.store');

    Route::get('/staff/create', [StaffUserController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffUserController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staff}/edit', [StaffUserController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staff}', [StaffUserController::class, 'update'])->name('staff.update');
    Route::patch('/staff/{staff}/status', [StaffUserController::class, 'status'])->name('staff.status');
    Route::get('/staff', [StaffUserController::class, 'index'])->name('staff.index');

    Route::get('/extra-lectures', [ExtraLectureRequestController::class, 'index'])->name('extra-lectures.index');
    Route::get('/extra-lectures/create', [ExtraLectureRequestController::class, 'create'])->name('extra-lectures.create');
    Route::post('/extra-lectures', [ExtraLectureRequestController::class, 'store'])->name('extra-lectures.store');
    Route::patch('/extra-lectures/{extraLectureRequest}/decision', [ExtraLectureRequestController::class, 'decide'])
        ->name('extra-lectures.decide');

    Route::get('/reports/class-attendance/export', [ReportController::class, 'exportClassAttendance'])
        ->name('reports.class-attendance.export');
    Route::get('/reports/subject-attendance/export', [ReportController::class, 'exportSubjectAttendance'])
        ->name('reports.subject-attendance.export');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/assignments/create', [FacultyAssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/assignments', [FacultyAssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/assignments/{assignment}/edit', [FacultyAssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('/assignments/{assignment}', [FacultyAssignmentController::class, 'update'])->name('assignments.update');
    Route::patch('/assignments/{assignment}/status', [FacultyAssignmentController::class, 'status'])->name('assignments.status');
    Route::delete('/assignments/{assignment}', [FacultyAssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::get('/assignments', [FacultyAssignmentController::class, 'index'])->name('assignments.index');

    Route::get('/timetables/faculty', [TimetableController::class, 'faculty'])->name('timetables.faculty');
    Route::get('/timetables/slots', [TimetableController::class, 'slots'])->name('timetables.slots');
    Route::get('/timetables/create', [TimetableController::class, 'create'])->name('timetables.create');
    Route::post('/timetables', [TimetableController::class, 'store'])->name('timetables.store');
    Route::get('/timetables/{timetable}/edit', [TimetableController::class, 'edit'])->name('timetables.edit');
    Route::put('/timetables/{timetable}', [TimetableController::class, 'update'])->name('timetables.update');
    Route::delete('/timetables/{timetable}', [TimetableController::class, 'destroy'])->name('timetables.destroy');
    Route::get('/timetables', [TimetableController::class, 'index'])->name('timetables.index');

    Route::get('/notices', [NoticeController::class, 'index'])->name('notices.index');
    Route::post('/notices', [NoticeController::class, 'store'])->name('notices.store');
    Route::delete('/notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');

    Route::get('/leaves/student', [StudentLeaveController::class, 'index'])->name('leaves.student.index');
    Route::post('/leaves/student', [StudentLeaveController::class, 'store'])->name('leaves.student.store');
    Route::get('/leaves/student/approvals', [StudentLeaveController::class, 'hodIndex'])->name('leaves.hod.index');
    Route::patch('/leaves/student/approvals/{leaveRequest}/decision', [StudentLeaveController::class, 'decide'])
        ->name('leaves.hod.decide');

    Route::get('/leaves/faculty', [FacultyLeaveController::class, 'index'])->name('leaves.faculty.index');
    Route::post('/leaves/faculty', [FacultyLeaveController::class, 'store'])->name('leaves.faculty.store');
    Route::get('/leaves/faculty/approvals', [FacultyLeaveController::class, 'hodIndex'])->name('leaves.faculty.hod.index');
    Route::patch('/leaves/faculty/approvals/{facultyLeaveRequest}/decision', [FacultyLeaveController::class, 'decide'])
        ->name('leaves.faculty.hod.decide');

    Route::get('/marks', [InternalMarkController::class, 'index'])->name('marks.index');
    Route::get('/marks/{subjectAssignment}/configure', [InternalMarkController::class, 'configureCreate'])->name('marks.configure.create');
    Route::post('/marks/{subjectAssignment}/configure', [InternalMarkController::class, 'configureStore'])->name('marks.configure.store');
    Route::get('/marks/{subjectAssignment}', [InternalMarkController::class, 'show'])->name('marks.show');
    Route::get('/marks/{subjectAssignment}/export', [InternalMarkController::class, 'export'])->name('marks.export');
    Route::post('/marks/{subjectAssignment}', [InternalMarkController::class, 'store'])->name('marks.store');
    Route::post('/marks/{subjectAssignment}/submit', [InternalMarkController::class, 'submit'])->name('marks.submit');
    Route::post('/marks/{subjectAssignment}/unlock', [InternalMarkController::class, 'unlock'])->name('marks.unlock');
    Route::post('/marks/{subjectAssignment}/submit-to-exam', [InternalMarkController::class, 'submitToExam'])->name('marks.submit-to-exam');
    Route::get('/my-marks', [InternalMarkController::class, 'studentView'])->name('marks.student');
    Route::post('/marks/{subjectAssignment}/release-external', [InternalMarkController::class, 'releaseExternal'])->name('marks.release-external');
    Route::post('/marks/{subjectAssignment}/store-external', [InternalMarkController::class, 'storeExternal'])->name('marks.store-external');
    Route::post('/marks/{subjectAssignment}/submit-external', [InternalMarkController::class, 'submitExternal'])->name('marks.submit-external');

    // Defaulters System
    Route::get('/defaulters', [DefaulterWarningController::class, 'index'])->name('defaulters.index');
    Route::get('/defaulters/{student}/letter', [DefaulterWarningController::class, 'show'])->name('defaulters.warning-letter');
    Route::post('/defaulters/{student}/parent-alert', [DefaulterWarningController::class, 'sendParentAlert'])->name('defaulters.parent-alert');

    // Faculty Attendance Submission Heatmap
    Route::get('/submission-heatmap', [AttendanceHeatmapController::class, 'index'])->name('attendance.heatmap');
    Route::get('/submission-heatmap/{date}', [AttendanceHeatmapController::class, 'showDayDetails'])->name('attendance.heatmap.details');

    // Hall Tickets Clearance Portal
    Route::get('/exam/hall-tickets', [ExamHallTicketController::class, 'index'])->name('exam.hall-tickets.index');
    Route::get('/exam/hall-tickets/generator', [ExamHallTicketController::class, 'generator'])->name('exam.hall-tickets.generator');
    Route::post('/exam/hall-tickets/{student}/waiver', [ExamHallTicketController::class, 'storeWaiver'])->name('exam.hall-tickets.store-waiver');
    Route::delete('/exam/hall-tickets/{student}/waiver', [ExamHallTicketController::class, 'destroyWaiver'])->name('exam.hall-tickets.destroy-waiver');
    Route::get('/my-hall-ticket', [ExamHallTicketController::class, 'studentHallTicket'])->name('student.hall-ticket.show');
    Route::get('/my-hall-ticket/download', [ExamHallTicketController::class, 'downloadHallTicket'])->name('student.hall-ticket.download');

    // Re-Evaluation & Marks Scrutiny Pipeline
    Route::get('/my-re-evaluations', [ReEvaluationController::class, 'studentIndex'])->name('student.re-evaluation.index');
    Route::post('/my-re-evaluations/{subjectAssignment}/apply', [ReEvaluationController::class, 'studentStore'])->name('student.re-evaluation.store');
    Route::get('/exam/scrutiny', [ReEvaluationController::class, 'coordinatorIndex'])->name('exam.scrutiny.index');
    Route::post('/exam/scrutiny/{requestItem}/assign', [ReEvaluationController::class, 'coordinatorAssign'])->name('exam.scrutiny.assign');
    Route::get('/faculty/scrutiny', [ReEvaluationController::class, 'facultyIndex'])->name('faculty.scrutiny.index');
    Route::post('/faculty/scrutiny/{requestItem}/submit', [ReEvaluationController::class, 'facultyScrutinize'])->name('faculty.scrutiny.submit');
    Route::post('/exam/scrutiny/{requestItem}/approve', [ReEvaluationController::class, 'coordinatorApprove'])->name('exam.scrutiny.approve');
    Route::post('/exam/scrutiny/{requestItem}/reject', [ReEvaluationController::class, 'coordinatorReject'])->name('exam.scrutiny.reject');
});
