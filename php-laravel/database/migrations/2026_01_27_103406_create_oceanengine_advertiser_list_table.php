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
        Schema::create('oceanengine_advertiser_list', function (Blueprint $table) {
            $table->id();
            $table->integer('oceanengine_id')->comment('巨量引擎主表id');
            $table->string('advertiser_id')->comment('账户id');
            $table->string('advertiser_name')->comment('账户名称');
            $table->string('advertiser_type')->comment('账户类型; QIANCHUAN：千川');
            $table->string('company')->nullable()->comment('公司名');
            $table->string('first_industry_name')->nullable()->comment('一级行业名');
            $table->string('second_industry_name')->nullable()->comment('二级行业名');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('id');
            $table->index('oceanengine_id');
            $table->index('advertiser_id');
            $table->index('company');
            $table->index('first_industry_name');
            $table->index('second_industry_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oceanengine_advertiser_list');
    }
};
