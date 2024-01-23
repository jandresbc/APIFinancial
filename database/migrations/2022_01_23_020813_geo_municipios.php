<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GeoMunicipios extends Migration
{
 /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('geo_municipios'))
        {
            Schema::create('geo_municipios', function (Blueprint $table) {
                {
                    $table->id('id');
                    $table->integer('departamento_id')->nullable(true);
                    $table->string('nombre', 50)->nullable(false);
                    $table->integer('cp')->nullable(false)->default(true);
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
        Schema::dropIfExists('geo_municipios');
    }
}
