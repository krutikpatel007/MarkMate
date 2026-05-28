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
        Schema::create('internal_marks_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('max_marks', 5, 2);
            $table->timestamps();

            $table->unique(['subject_assignment_id', 'name']);
        });

        Schema::create('internal_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('mid_sem_30', 5, 2)->nullable();
            $table->decimal('mid_sem_20', 5, 2)->nullable();
            $table->decimal('cie_30', 5, 2)->default(0.00);
            $table->decimal('total_50', 5, 2)->default(0.00);
            
            $table->string('status')->default('draft')->index(); // 'draft', 'submitted'
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subject_assignment_id', 'student_id']);
        });

        Schema::create('internal_marks_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_mark_id')->constrained('internal_marks')->cascadeOnDelete();
            $table->foreignId('internal_marks_component_id')->constrained('internal_marks_components')->cascadeOnDelete();
            $table->decimal('marks_obtained', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['internal_mark_id', 'internal_marks_component_id'], 'mark_value_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_marks_values');
        Schema::dropIfExists('internal_marks');
        Schema::dropIfExists('internal_marks_components');
    }
};
