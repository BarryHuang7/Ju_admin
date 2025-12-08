<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_list', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('content')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_url')->nullable();
            $table->dateTime('file_date')->nullable();
            $table->integer('is_admin')->nullable()->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('update_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_list');
    }
}
