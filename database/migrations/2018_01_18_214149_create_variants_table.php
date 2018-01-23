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
            $table->bigInteger('variant_id');
            $table->bigInteger('product_id');
            $table->bigInteger('product_table_id');
            $table->string('title');
            $table->string('sku');
            $table->string('price');
            $table->string('compare_at_price');
            $table->string('user_type');
            $table->string('variant_name');
            $table->string('qty');
            $table->integer('position');
            $table->string('weight');
            $table->string('weight_unit');
            $table->string('requires_shipping');
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
