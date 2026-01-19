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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->comment('唯一标识符');
            $table->string('original_name')->comment('原始文件名');
            $table->string('file_name')->comment('存储文件名');
            $table->string('path')->comment('存储路径');
            $table->string('mime_type')->comment('文件类型');
            $table->unsignedBigInteger('size')->comment('文件大小(字节)');
            // $table->integer('duration')->nullable()->comment('视频时长(秒)');
            // $table->integer('width')->nullable()->comment('视频宽度');
            // $table->integer('height')->nullable()->comment('视频高度');
            $table->text('chunks')->nullable()->comment('已上传分片信息');
            $table->integer('total_chunks')->comment('总分片数');
            $table->string('status')->default('uploading')->comment('上传中uploading, 合并中merging, 处理中processing, 完成completed, 失败failed');
            // $table->string('thumbnail_path')->nullable()->comment('缩略图路径');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('uuid');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
