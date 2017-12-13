<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionalEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactional_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('from_name')->nullable();
            $table->string('from_email');
            $table->string('to_name')->nullable();
            $table->string('to_email');
            $table->string('email_subject');
            $table->integer('vanity_website_id')->nullable();
            $table->string('form_name');
            $table->longText('request_data');
            $table->integer('leads_api_submission_status')->nullable();
            $table->integer('sendgrid_submission_status')->nullable();
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
        Schema::dropIfExists('transactional_emails');
    }
}
