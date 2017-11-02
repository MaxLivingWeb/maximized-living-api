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
            $table->integer('affiliate_id');
            $table->string('name');
            $table->string('zip_postal_code');
            $table->double('latitude', 13, 10);
            $table->double('longitude', 13, 10);
            $table->string('telephone');
            $table->string('telephone_ext');
            $table->string('fax');
            $table->string('email');
            $table->string('vanity_website_url');
            $table->string('slug');
            $table->string('pre_open_display_date');
            $table->string('opening_date');
            $table->string('closing_date');
            $table->boolean('daylight_savings_applies');
            $table->mediumText('operating_hours');
            $table->integer('timezone_id')->unsigned();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('timezone_id')->references('id')->on('timezones');
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
