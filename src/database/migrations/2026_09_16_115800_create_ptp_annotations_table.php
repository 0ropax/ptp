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
                ->onDelete("cascade");


            // Deleting the original point annotation sets this reference to null.
            // The generated PTP annotation with polygon coordinates are kept.
            $table->integer("source_annotation_id")->nullable();
            $table->foreign("source_annotation_id")
                ->references("id")
                ->on("image_annotations")
                ->onDelete("set null");


            // Deleting an accepted polygon sets this reference to null.
            $table->integer("annotation_id")->nullable();
            $table->foreign("annotation_id")
                ->references("id")
                ->on("image_annotations")
                ->onDelete("set null");


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
