<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_code')->unique();
            $table->string('department_name');
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('program_code');
            $table->string('program_name');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['department_id', 'program_code']);
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('semester_no');
            $table->string('semester_name');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['program_id', 'semester_no']);
        });

        Schema::create('class_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('section_name', 20);
            $table->string('display_name');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['semester_id', 'section_name']);
        });

        Schema::create('faculty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('employee_code')->unique();
            $table->string('designation')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->string('enrollment_no')->unique();
            $table->string('roll_no')->nullable();
            $table->string('mobile')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('subject_code');
            $table->string('subject_name');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['semester_id', 'subject_code']);
        });

        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year')->default('2026-27');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['subject_id', 'class_section_id', 'academic_year'], 'subject_assignment_unique');
        });

        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('lecture_no');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['day_of_week', 'start_time']);
        });

        Schema::create('extra_lecture_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->date('requested_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('session_type')->default('extra');
            $table->text('reason');
            $table->string('approval_status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });

        Schema::create('lecture_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extra_lecture_request_id')->nullable()->constrained()->nullOnDelete();
            $table->date('lecture_date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedTinyInteger('lecture_no')->nullable();
            $table->string('session_type')->default('regular')->index();
            $table->string('status')->default('scheduled')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['subject_assignment_id', 'lecture_date'], 'lecture_session_assignment_date_index');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('absent')->index();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['lecture_session_id', 'student_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('lecture_sessions');
        Schema::dropIfExists('extra_lecture_requests');
        Schema::dropIfExists('timetables');
        Schema::dropIfExists('subject_assignments');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('students');
        Schema::dropIfExists('faculty');
        Schema::dropIfExists('class_sections');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
    }
};
