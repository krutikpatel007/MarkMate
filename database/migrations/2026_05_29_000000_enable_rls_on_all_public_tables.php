<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            foreach ($tables as $table) {
                $tableName = $table->tablename;
                DB::statement("ALTER TABLE \"public\".\"{$tableName}\" ENABLE ROW LEVEL SECURITY;");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            foreach ($tables as $table) {
                $tableName = $table->tablename;
                DB::statement("ALTER TABLE \"public\".\"{$tableName}\" DISABLE ROW LEVEL SECURITY;");
            }
        }
    }
};
