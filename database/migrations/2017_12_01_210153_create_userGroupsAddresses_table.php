<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGroupsAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usergroup_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_group_id')->unsigned();
            $table->integer('address_id')->unsigned();
            $table->integer('address_type_id')->unsigned();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_group_id')->references('id')->on('user_groups');
            $table->foreign('address_id')->references('id')->on('addresses');
            $table->foreign('address_type_id')->references('id')->on('address_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usergroup_addresses');
    }
}
