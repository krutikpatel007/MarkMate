<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->dropUnique('subject_assignment_unique');
            });

            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->unique(
                    ['subject_id', 'class_section_id', 'academic_year', 'faculty_id'],
                    'subject_assignment_unique'
                );
            });
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->dropUnique('subject_assignment_unique');
            });

            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->unique(['subject_id', 'class_section_id', 'academic_year'], 'subject_assignment_unique');
            });
        });
    }
};
