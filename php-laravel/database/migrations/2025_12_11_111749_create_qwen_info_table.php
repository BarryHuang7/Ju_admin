<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQwenInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qwen_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ip');
            $table->integer('prompt_tokens')->nullable()->comment('输入的 Token 数，plus为0.0008元/千Token');
            $table->integer('completion_tokens')->nullable()->comment('模型输出的 Token 数，plus为0.002元/千Token');
            $table->text('content')->comment('输入的消息内容');
            $table->text('assistant_content')->nullable()->comment('模型输出的消息内容');
            $table->longText('assistant_response_body')->nullable()->comment('模型输出的完整响应体');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qwen_info');
    }
}
