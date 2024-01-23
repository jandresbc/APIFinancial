<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GeoDepartamentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('geo_departamentos'))
        {
            Schema::create('geo_departamentos', function (Blueprint $table) {
                {
                    $table->id('id');
                    $table->integer('pais_id')->nullable(true);
                    $table->integer('orden')->nullable(true);
                    $table->string('nombre', 50)->nullable(false);
                    $table->boolean('activo')->nullable(false)->default(true);
                    $table->softDeletes();
                    $table->timestamps();
                }
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
        Schema::dropIfExists('geo_departamentos');
    }
}
