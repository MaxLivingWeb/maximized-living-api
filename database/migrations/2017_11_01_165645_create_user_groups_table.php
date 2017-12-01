<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('group_name')->unique();
            $table->bigInteger('discount_id')->nullable()->unsigned();
            $table->integer('legacy_affiliate_id')->nullable()->unsigned();
            $table->integer('commission_id')->nullable()->unsigned()->index();
            $table->integer('location_id')->nullable()->unsigned();
            $table->timestamps();

            $table->foreign('commission_id')->references('id')->on('commission_groups');
            $table->foreign('location_id')->references('id')->on('locations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_groups');
    }
}
