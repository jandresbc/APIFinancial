<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SiigoInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('siigo_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('hash_id',50)->nullable(true);
            $table->integer('loan_id')->nullable(true);
            $table->integer('external_id')->nullable(true);
            $table->integer('quota')->nullable(true);
            $table->date('due_date')->nullable(true);
            $table->integer("number")->nullable(false);
            $table->string("due_prefix",10)->default(config('Siigo.siigo_invoice_types'));
            $table->string("ERP_doc_name", 20)->nullable(true);
            $table->timestamp("ERP_doc_date")->nullable(true);
            $table->integer('account_id')->nullable(true);
            $table->string("identification",20)->nullable(false);
            $table->string("first_name", 50)->nullable(false);
            $table->string("last_name", 50)->nullable(false);
            $table->decimal("total_value", 16, 2)->nullable(false);
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
        Schema::dropIfExists('siigo_invoices');

    }
}
