<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxtvTokenField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_groups', function(Blueprint $table) {
            $table->string('maxtv_token', 64)->nullable()->unique()->after('event_promoter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_groups', function(Blueprint $table) {
            $table->dropColumn('maxtv_token');
        });
    }
}
