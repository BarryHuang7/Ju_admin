<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSendEmailInfoTable extends Migration
{
    // php artisan migrate
    // php artisan make:migration create_send_email_info_table
    // php artisan migrate --path=/database/migrations/2025_12_08_062039_create_send_email_info_table.php

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('send_email_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email');
            $table->string('ip');
            $table->integer('isSuccessful')->comment('邮箱是否发送成功:1成功,0失败');
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
        Schema::dropIfExists('send_email_info');
    }
}
