<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_evaluation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'recount', 'rechecking'
            $table->string('status')->default('requested')->index(); // 'requested', 'assigned', 'scrutinized', 'completed'
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('original_marks', 5, 2);
            $table->decimal('revised_marks', 5, 2)->nullable();
            $table->text('student_remarks')->nullable();
            $table->text('evaluator_remarks')->nullable();
            $table->timestamp('re_evaluated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('coordinator_remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'subject_assignment_id'], 're_eval_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_evaluation_requests');
    }
};
