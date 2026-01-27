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
            $table->text('state')->nullable()->comment('自定义参数，可用于传递自定义信息，回调时会原样带回');
            $table->string('uid')->comment('用户id');
            $table->string('access_token')->nullable()->comment('接口授权码');
            $table->string('refresh_token')->nullable()->comment('接口刷新码');
            $table->string('cc_account_id')->nullable()->comment('已授权账户id');
            $table->string('cc_account_name')->nullable()->comment('已授权账户名称');
            $table->string('account_role')->nullable()->comment('账户类型，枚举值：
                ADVERTISER 客户
                CUSTOMER_ADMIN 普通版工作台-管理员
                CUSTOMER_OPERATOR 普通版工作台-协作者
                AGENT 代理商
                CHILD_AGENT 二级代理商
                PLATFORM_ROLE_STAR 星图账户
                PLATFORM_ROLE_SHOP_ACCOUNT 抖音店铺账户
                PLATFORM_ROLE_QIANCHUAN_AGENT 千川代理商
                PLATFORM_ROLE_STAR_AGENT 星图代理商
                PLATFORM_ROLE_AWEME 抖音号
                PLATFORM_ROLE_STAR_MCN 星图MCN机构
                PLATFORM_ROLE_STAR_ISV 星图服务商
                AGENT_SYSTEM_ACCOUNT 代理商系统账户
                PLATFORM_ROLE_LOCAL_AGENT 本地推代理商
                PLATFORM_ROLE_YUNTU_BRAND_ISV_ADMIN 云图品牌服务商管理员
                PLATFORM_ROLE_LIFE 抖音来客账户
                PLATFORM_ROLE_ENTERPRISE_BP_ADMIN 升级版工作台管理员
                PLATFORM_ROLE_ENTERPRISE_BP_OPERATOR 升级版工作台协作者
            ');
            $table->integer('is_valid')->nullable()->comment('授权有效性');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('id');
            $table->index('uid');
            $table->index('cc_account_id');
            $table->index('cc_account_name');
            $table->index('account_role');
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
