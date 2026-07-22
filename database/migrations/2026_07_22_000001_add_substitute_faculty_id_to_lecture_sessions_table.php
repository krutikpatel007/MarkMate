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
        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->foreignId('substitute_faculty_id')->nullable()->constrained('faculty')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->dropForeign(['substitute_faculty_id']);
            $table->dropColumn('substitute_faculty_id');
        });
    }
};
