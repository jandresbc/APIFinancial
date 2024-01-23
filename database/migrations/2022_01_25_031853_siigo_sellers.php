<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SiigoSellers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('siigo_sellers'))
        {
            Schema::create('siigo_sellers', function (Blueprint $table) {
                $table->id();
                $table->string("username",50)->nullable(true);
                $table->string("first_name",50)->nullable(true);
                $table->string("last_name",50)->nullable(true);
                $table->string("email",100)->nullable(true);
                $table->boolean("active")->nullable(true);
                $table->string("identification",30)->nullable(true);
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
        Schema::dropIfExists('siigo_sellers');

    }
}
