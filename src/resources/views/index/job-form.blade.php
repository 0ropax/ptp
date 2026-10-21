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
    </p>


    <form>
        <fieldset>
            <legend>Scope of conversion</legend>
            <div>
                Choose which points in this volume you want to include in the conversion:
            </div>
        </fieldset>

        <!--- Options and startJobButton --->
        <div id="ptp-job-form"></div>

    </form>
</div>
