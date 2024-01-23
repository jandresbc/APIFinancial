<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MessageWhatsappLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('message_whatsapp_log', function (Blueprint $table) {
            $table->id();
            $table->string('validator');
            $table->string('phone_client');
            $table->string('id_identification');
            $table->string('whatsapp_id');
            $table->string('from');
            $table->string('to');
            $table->string('ack');
            $table->string('type');
            $table->string('body');
            $table->boolean('fromMe');
            $table->integer('time');
            $table->string('response_client');
            $table->string('hash');
            $table->string('state');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_flows');
    }
}
