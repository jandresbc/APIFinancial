<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SiigoDataLogs extends Migration
{
    protected $connection = 'logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('siigo_data_logs'))
        {
            Schema::create('siigo_data_logs', function (Blueprint $table) {
                $table->id();
                $table->string('siigo_log_id')->nullable('false');
                $table->integer('level')->nullable();
                $table->string('level_name', 20)->nullable();
                $table->json('data')->nullable();
                $table->boolean('error')->default(false);
                $table->string('message', 250)->nullable();
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
        Schema::dropIfExists('siigo_data_logs');
    }
}
