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
        Schema::create('product_stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_name')->comment('产品名称');
            $table->string('product_number', 50)->comment('产品编号');
            $table->integer('stock')->comment('库存数');
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->useCurrent()->comment('更新时间');
            $table->softDeletes();
            // 索引
            $table->unique('id');
            $table->index('product_name');
            $table->unique('product_number');
            $table->index(['product_number', 'stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock');
    }
};
