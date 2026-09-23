<?php

namespace Biigle\Modules\Ptp;


use Biigle\Modules\Laserpoints\Volume;
use Illuminate\Database\Eloquent\Model;
use Biigle\ImageAnnotation;


class PtpJob extends Model {

    protected $fillable = ["volume_id", "status", "options_params"];

    protected $casts = [ "options_params" => "array" ];

    public function volume() {
        return $this->belongsTo(Volume::class);
    }

    public function annotations() {
        return $this->hasMany(PtpAnnotation::class, "ptp_job_id");
    }


}
