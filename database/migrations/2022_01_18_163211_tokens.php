<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Tokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('siigo_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->string('api_version',3)->default('v1');
            $table->string('token', 1500);
            $table->string('refresh_token', 250)->nullable(true);
            $table->string('token_type', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_in')->nullable(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('siigo_tokens');
    }
}
