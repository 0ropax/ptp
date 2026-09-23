<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("ptp_jobs", function (Blueprint $table) {
            $table->increments("id");

            $table->integer("volume_id");
            $table->foreign("volume_id")
                ->references("id")
                ->on("volumes");


            $table->string("status");
            $table->json("options_params");
            $table->timestamps();
        });
    }

    #TODO : nutzer später noch hinzufügen vielleicht
    #$table->integer("user_id")->unsigned();

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("ptp_jobs");
    }
};
