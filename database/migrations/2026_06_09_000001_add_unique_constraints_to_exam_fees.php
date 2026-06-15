<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_fees', function (Blueprint $table) {
            $table->unique('semester_id');
        });

        Schema::table('exam_fee_payments', function (Blueprint $table) {
            $table->unique(['student_id', 'exam_fee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_fee_payments', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'exam_fee_id']);
        });

        Schema::table('exam_fees', function (Blueprint $table) {
            $table->dropUnique(['semester_id']);
        });
    }
};
