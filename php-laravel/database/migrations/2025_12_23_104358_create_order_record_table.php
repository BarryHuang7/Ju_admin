<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_record', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ipAddress('ip');
            $table->string('user_id', 100)->comment('用户id');
            $table->string('order_no', 50)->comment('订单编号');
            $table->string('order_name')->comment('订单名称');
            $table->string('product_name')->comment('产品名称');
            $table->string('product_number', 50)->comment('产品编号');
            $table->integer('stock')->comment('入库数');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            // 索引
            $table->unique('id');
            $table->index('user_id');
            $table->index('order_no');
            $table->index('product_number');
            $table->index(['user_id', 'order_no']);
            $table->index(['user_id', 'product_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_record');
    }
}
