<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsergroupCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usergroup_collections', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('usergroup_id');
            $table->integer('collection_id');

            $table->foreign('usergroup_id')->references('id')->on('user_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usergroup_collections');
    }
}
