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
        /*
       | A PTP job is created whenever a user wants to perform the point-to-polygon
       | conversion for a volume using SAM. For each volume there can only be one
       | PTP job in the 'pending' or 'in-progress' state at a time. All project
       | editors, experts or admins can create and review PTP jobs.
       */
        Schema::create("ptp_jobs", function (Blueprint $table) {
            $table->increments("id");

            $table->integer("volume_id");
            $table->foreign("volume_id")
                ->references("id")
                ->on("volumes");

            $table->string("status");
            $table->json("conversion_options");
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
        Schema::dropIfExists("ptp_jobs");
    }
};
