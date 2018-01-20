<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('variantID');
            $table->integer('productID');
            $table->string('title');
            $table->string('sku');
            $table->string('price');
            $table->string('userType');
            $table->string('variantName');
            $table->string('qty');
            $table->integer('position');
            $table->string('weight');
            $table->string('weightUnit');
            $table->string('requiresShipping');
            $table->string('grams');
            $table->string('taxable');
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
        Schema::dropIfExists('variants');
    }
}
