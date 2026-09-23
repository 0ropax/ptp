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

        Schema::create("ptp_annotations", function (Blueprint $table) {
            $table->increments("id");

            $table->integer("ptp_job_id");
            $table->foreign("ptp_job_id")
                ->references("id")
                ->on("ptp_jobs")
                ->onDelete("cascade"); #wenn job gelöscht wird


            $table->integer("source_annotation_id");
            $table->foreign("source_annotation_id")
                ->references("id")
                ->on("image_annotations")
                ->onDelete("set null");
            #TODO ? source anno id null falls punkt gelöscht aber der datensatz bestehen bleiben soll mit polygon
            # später jobs löschen?


            $table->integer("annotation_id")->nullable();

            $table->json("points");

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
        Schema::dropIfExists("ptp_annotations");
    }
};
