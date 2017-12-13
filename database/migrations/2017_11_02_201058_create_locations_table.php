<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('telephone');
            $table->string('telephone_ext');
            $table->string('fax');
            $table->string('email');
            $table->string('vanity_website_url');
            $table->string('slug');
            $table->string('pre_open_display_date');
            $table->string('opening_date');
            $table->string('closing_date');
            $table->boolean('daylight_savings_applies')->default(1);
            $table->mediumText('operating_hours');

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
        Schema::dropIfExists('locations');
    }
}
