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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUuid('variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);

            // One variant per cart — no duplicate rows
            $table->unique(['cart_id', 'variant_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
