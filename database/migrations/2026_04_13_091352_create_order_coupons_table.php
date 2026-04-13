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
        Schema::create('order_coupons', function (Blueprint $table) {
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('coupon_id')->constrained()->restrictOnDelete();
            $table->decimal('discount_applied', 12, 2);
            $table->primary(['order_id', 'coupon_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_coupons');
    }
};
