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
        Schema::create('faculty_salary_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->decimal('basic_pay', 12, 2)->default(0.00);
            $table->decimal('hra', 12, 2)->default(0.00);
            $table->decimal('da', 12, 2)->default(0.00);
            $table->decimal('special_allowance', 12, 2)->default(0.00);
            $table->decimal('deductions', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('faculty_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('basic_pay', 12, 2);
            $table->decimal('hra', 12, 2);
            $table->decimal('da', 12, 2);
            $table->decimal('special_allowance', 12, 2);
            $table->decimal('deductions', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['faculty_id', 'month', 'year']);
        });

        Schema::create('faculty_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'subject_assignment_id']);
        });

        Schema::create('faculty_appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year');
            $table->unsignedTinyInteger('score_teaching');
            $table->unsignedTinyInteger('score_research');
            $table->unsignedTinyInteger('score_administrative');
            $table->decimal('overall_rating', 3, 2);
            $table->text('review_comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_appraisals');
        Schema::dropIfExists('faculty_feedbacks');
        Schema::dropIfExists('faculty_payslips');
        Schema::dropIfExists('faculty_salary_configs');
    }
};
