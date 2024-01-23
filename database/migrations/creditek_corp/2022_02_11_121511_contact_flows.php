<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ContactFlows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('contact_flows', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->enum('plataform', array_keys(['sms' => "SMS", 'whatssapp' => "Whatssapp", 'email' => "email"]));
            $table->string('template');
            $table->integer('day_execution');
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
