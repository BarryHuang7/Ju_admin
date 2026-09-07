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
        Schema::table('order_record', function (Blueprint $table) {
            $table->unsignedBigInteger('product_stock_id')->default(0)->before('product_name')->comment('产品库存id');
            $table->index('product_stock_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_record', function (Blueprint $table) {
            $table->dropColumn('product_stock_id');
        });
    }
};
