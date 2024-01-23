<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SiigoLogs extends Migration
{
    protected $connection = 'logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('siigo_logs'))
        {
            Schema::create('siigo_logs', function (Blueprint $table) {
                $table->id();
                $table->string('identification', 20)->nullable();
                $table->string('name', 100)->nullable();
                $table->string('email', 100)->nullable();
                $table->integer('loan')->nullable();
                $table->integer('quota')->nullable();
                $table->string('source', 20)->nullable();
                $table->string('type', 20)->nullable();
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
        Schema::dropIfExists('siigo_logs');
    }
}
