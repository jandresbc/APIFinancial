<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SiigoDocumentsType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('siigo_documents_type'))
        {
            Schema::create('siigo_documents_type', function (Blueprint $table) {
                {
                    $table->id('id');
                    $table->string('code', 10)->nullable(false);
                    $table->string('name', 50)->nullable(false); 
                    $table->string('description', 100)->nullable(true); 
                    $table->enum('type', ['FV','RC','NC','FC','CC'])->nullable(true); 
                    $table->boolean('active')->default(true); 
                    $table->boolean('seller_by_item')->nullable(true); 
                    $table->boolean('cost_center')->nullable(true); 
                    $table->boolean('cost_center_mandatory')->nullable(true); 
                    $table->boolean('automatic_number')->nullable(true); 
                    $table->integer('consecutive')->nullable(true); 
                    $table->enum('discount_type', ['Percentage','Value'])->nullable(true); 
                    $table->boolean('decimals')->nullable(true); 
                    $table->boolean('advance_payment')->nullable(true); 
                    $table->boolean('reteiva')->nullable(true); 
                    $table->boolean('reteica')->nullable(true); 
                    $table->boolean('self_withholding')->nullable(true); 
                    $table->integer('self_withholding_limit')->nullable(true); 
                    $table->enum('electronic_type', ['NoElectronic','ElectronicInvoice','ContingencyInvoice','ExportInvoice','ElectronicCreditNote'])->nullable(true); 
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
        Schema::dropIfExists('siigo_documents_type');
    }
}
