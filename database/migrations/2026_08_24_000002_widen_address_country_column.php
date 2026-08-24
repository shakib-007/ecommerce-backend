<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE addresses ALTER COLUMN country TYPE VARCHAR(100)');
        DB::statement("ALTER TABLE addresses ALTER COLUMN country SET DEFAULT 'Bangladesh'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE addresses ALTER COLUMN country SET DEFAULT 'BD'");
        DB::statement('ALTER TABLE addresses ALTER COLUMN country TYPE VARCHAR(2)');
    }
};
