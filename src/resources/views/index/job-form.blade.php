<div>
    <h3>Create a new PTP job
        <a
            href="{{  route("manual-tutorials", ["ptp", "ptp"]) }}"
            target="_blank"
            class="btn btn-default btn-xs pull-right"
            title="More information on PTP">

            <span class="fa fa-info-circle" aria-hidden="true"></span>
        </a>
    </h3>

    <p>
        The Magic SAM point conversion tool allows you to convert existing point annotations of a whole image
        volume to polygons.
        <br>
        <br>
        Depending on the count of points to convert, a job can run for many hours or even a day.
        Please choose your settings carefully before you submit a new job.
        Overall points to convert in this volume: {{ $pointCount }}.
    </p>


    <form>
        <fieldset>
            <legend>Scope of conversion</legend>
            <div>
                Choose which points in this volume you want to include in the conversion:
            </div>
        </fieldset>

        <!--- Conversion options --->
        <fieldset>
            <div class="form-group">
                <div class="radio">
                    <label for="point_to_polygon_sam">
                        <input type="radio" id="include_converted_points_sam" name="converted_points_sam" value="included">
                        <strong>Convert all points</strong>
                        <div class="help-block">
                            Converts all point annotations in this volume, including points that were successfully converted
                            in previous jobs. All existing permanent polygon annotations are kept.
                        </div>
                    </label>
                </div>
            </div>


            <div class="form-group">
                <div class="radio">
                    <label for="point_to_polygon_sam">
                        <input type="radio" id="ignore_converted_points_sam" name="converted_points_sam" value="ignored" checked>
                        <strong>Ignore already converted points</strong>
                        <div class="help-block">
                            Only converts the points which do not have an accepted permanent polygon. Previously failed
                            or rejected point conversions are processed again.
                            All existing permanent polygon annotations are kept.
                        </div>

                    </label>
                </div>
            </div>


            <div id="ptp-label-selector"></div> <!--TODO !-->

        </fieldset>

        <!--- Create job Button --->
        <div id="ptp-job-form"></div>


    </form>
</div>
