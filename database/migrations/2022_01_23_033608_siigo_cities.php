<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SiigoCities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('siigo_cities'))
        {
            Schema::create('siigo_cities', function (Blueprint $table) {
                $table->id();
                $table->string('CityCode', 10);  
                $table->string('CityName', 50);  
                $table->string('StateCode', 10);
                $table->string('StateName', 50);
                $table->string('CountryCode', 10);
                $table->string('CountryName',50);
                $table->softDeletes();
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
        Schema::dropIfExists('siigo_cities');
    }
}
