<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('variant_attributes', function (Blueprint $table) {
            $table->foreignUuid('variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->foreignUuid('attribute_value_id')
                ->constrained('attribute_values')
                ->cascadeOnDelete();
            $table->primary(['variant_id', 'attribute_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_attributes');
    }
};
