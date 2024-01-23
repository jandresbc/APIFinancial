<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('siigo_payment_types'))
        {
            Schema::create('siigo_payment_types', function (Blueprint $table) {
                $table->id();
                $table->string("name",50)->nullable(false);
                $table->string("type",50)->nullable(true);
                $table->boolean("active")->nullable(false);
                $table->string("due_date",40)->nullable(true);
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
        Schema::dropIfExists('siigo_payment_types');
    }
}
