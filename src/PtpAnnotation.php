<?php

namespace Biigle\Modules\Ptp;


use Illuminate\Database\Eloquent\Model;
use Biigle\ImageAnnotation;

class PtpAnnotation extends Model
{


    protected $fillable = [
        "ptp_job_id",
        "source_annotation_id",
        "annotation_id",
        "points",
    ];

    protected $casts = [
        "points" => "array",
    ];


    public function job() {

        return $this->belongsTo("ptp_job_id"); #PtpJpb noch ändern
    }

    public function sourceAnnotation() {

        return $this->belongsTo(ImageAnnotation::class, "source_annotation_id");
    }

    public function annotation() {
        return $this->belongsTo(ImageAnnotation::class, "annotation_id");
    }
}
