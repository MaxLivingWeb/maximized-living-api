<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWholesalerToUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->boolean('wholesaler')->default(false)->after('legacy_affiliate_id');
        });

        DB::table('user_groups')
            ->whereNotNull('discount_id')
            ->update(['wholesaler' => true]);

        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropColumn('discount_id');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
