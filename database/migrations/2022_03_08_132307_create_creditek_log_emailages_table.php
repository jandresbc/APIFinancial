<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditekLogEmailagesTable extends Migration
{

    protected $connection = 'logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('creditek_log_emailages')) {
            Schema::create('creditek_log_emailages', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->text('message');
                $table->enum('type_log', ['info','error','warning'])->default('info');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('creditek_log_emailages');
    }
}
