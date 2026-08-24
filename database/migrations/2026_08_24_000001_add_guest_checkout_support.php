<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE addresses ALTER COLUMN user_id DROP NOT NULL');

        Schema::table('addresses', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE orders ALTER COLUMN user_id DROP NOT NULL');

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_phone')->nullable()->after('guest_email');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_email', 'guest_phone']);
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE orders ALTER COLUMN user_id SET NOT NULL');

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE addresses ALTER COLUMN user_id SET NOT NULL');

        Schema::table('addresses', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
