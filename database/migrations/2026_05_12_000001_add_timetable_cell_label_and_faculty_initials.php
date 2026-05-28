<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->string('display_initials', 12)->nullable()->after('designation');
        });

        Schema::table('timetables', function (Blueprint $table) {
            $table->string('cell_label', 64)->nullable()->after('lecture_no');
        });
    }

    public function down(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->dropColumn('display_initials');
        });

        Schema::table('timetables', function (Blueprint $table) {
            $table->dropColumn('cell_label');
        });
    }
};
