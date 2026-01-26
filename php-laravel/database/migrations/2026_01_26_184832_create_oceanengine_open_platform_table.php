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
        Schema::create('oceanengine_open_platform', function (Blueprint $table) {
            $table->id();
            $table->string('auth_code')->comment('用户授权码');
            $table->text('scope')->comment('用户授权范围');
            $table->integer('material_auth_status')->comment('是否敏感物料授权, 1 已敏感物料授权, 0 未敏感物料授权');
            $table->text('state')->comment('自定义参数，可用于传递自定义信息，回调时会原样带回');
            $table->string('uid')->comment('用户id');
            $table->string('access_token')->comment('接口授权码');
            $table->string('refresh_token')->comment('接口刷新码');
            $table->string('advertiser_id')->comment('已授权账户id');
            $table->string('advertiser_name')->comment('已授权账户名称');
            $table->text('advertiser_list')->comment('纵横工作台账户列表信息');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oceanengine_open_platform');
    }
};
