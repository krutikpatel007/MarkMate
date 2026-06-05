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
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->string('external_marks_status')->default('not_released')->index();
        });

        Schema::table('internal_marks', function (Blueprint $table) {
            $table->decimal('external_50', 5, 2)->nullable();
            $table->decimal('total_100', 5, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->dropColumn('external_marks_status');
        });

        Schema::table('internal_marks', function (Blueprint $table) {
            $table->dropColumn(['external_50', 'total_100']);
        });
    }
};
