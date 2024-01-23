<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditekDataEmailagesTable extends Migration
{
    protected $connection = 'logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('creditek_data_emailages')) {
            Schema::create('creditek_data_emailages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('creditek_log_emailage_id');
                $table->datetime('consultation_date');
                $table->datetime('due_date')->nullable();
                $table->json('data')->nullable();
                $table->text('reason');
                $table->integer('riskBand')->nullable();
                $table->integer('riskScore')->nullable();
                $table->enum('status', ['APRO', 'RECH']);
                $table->foreign('creditek_log_emailage_id')->references('id')->on('creditek_log_emailages');
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
        Schema::dropIfExists('creditek_data_emailages');
    }
}
