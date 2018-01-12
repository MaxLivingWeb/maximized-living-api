<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUniqueGroupName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_groups', function(Blueprint $table)
        {
            $keyExists = DB::select(
                DB::raw(
                    'SHOW KEYS
                     FROM user_groups
                     WHERE Key_name=\'user_groups_group_name_unique\''
                )
            );

            if($keyExists) {
                $table->dropIndex('user_groups_group_name_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_groups', function(Blueprint $table)
        {
            $table->unique('group_name');
        });
    }
}
