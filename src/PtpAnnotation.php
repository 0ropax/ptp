<?php

namespace Biigle\Modules\Ptp;


use Illuminate\Database\Eloquent\Model;
use Biigle\ImageAnnotation;
use Biigle\Image;
use Biigle\Label;

/**
 * TODO
 */
class PtpAnnotation extends Model
{

    /** TODO
     * @var string[]
     */
    protected $fillable = [
        "ptp_job_id",
        "source_annotation_id",
        "annotation_id",
        "points",
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        "points" => "array",
    ];

    /**
     * Gets the PTP job that belongs to this polygon annotation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function job() {

        return $this->belongsTo(PtpJob::class, "ptp_job_id");
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function image() {

        return $this->belongsTo(Image::class, "image_id");
    }


    public function label() {

        return $this->belongsTo(Label::class, "label_id");
    }

    /**
     * Gets the original point that was used by SAM in this conversion.
     * The original source annotation may be null if it was deleted.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourceAnnotation() {

        return $this->belongsTo(ImageAnnotation::class, "source_annotation_id");
    }

    /**
     * Gets the polygon annotation which was set as permanent through the PTP review process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function annotation() {

        return $this->belongsTo(ImageAnnotation::class, "annotation_id");
    }


}
