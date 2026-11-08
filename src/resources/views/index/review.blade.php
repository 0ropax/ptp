@section("full-navbar", true)


<div class="sidebar-container__content">


    <ptp-patch-grid
        :images="filteredAnnotations"
        empty-url="{{ asset(config("thumbnails.empty_url")) }}"
        :selectable="true"
        selected-icons="times"
        v-on:select="handleSelect">
    </ptp-patch-grid>



    <div v-if="selectedLabel && filteredAnnotations.length === 0" class="text-info"
    style="position:absolute; top: 50%; left:50%; transform: translate(-50%, -50%);">
        There are no annotations with the label <strong v-text="selectedLabel.name"></strong>.
    </div>


</div>





<sidebar v-cloak open-tab="information">


    <sidebar-tab
        name="information"
        icon="info-circle"
        title="Job information">

        <div class="sidebar-tab__content sidebar-tab__content--ptp">
            <div class="ptp-job-tab__top">
            <p>
                Job #{{  $ptpJob->id  }}


                @if ($ptpJob->status === "waiting-for-review" || $ptpJob->status === "review-completed")
                    <span class="label label-success">conversion finished</span>

                <br>
                    created <span title="{{$ptpJob->created_at->toIso8601String()}}">{{$ptpJob->created_at->diffForHumans()}}. </span>
                    <br>


                <br>
                    Status: {{  $ptpJob->status }}

                @elseif($ptpJob->status === "failed" )
                    <span class="label label-failed">conversion failed</span>
                @else
                    <span class="label label-default">not finished</span>
                @endif

            </p>







            <table class="table">
                <thead>
                    <tr>
                        <th colspan="2"> Job Information</th>
                    </tr>
                </thead>

                <tbody>





                <tr>
                    <td>
                        Ignore previous conversions?
                    </td>

                    <td class="text-right">
                        @if( $ptpJob->conversion_options["already_converted_points"] === "ignored" )
                            <code>yes</code>
                        @else
                            <code>no</code>
                        @endif
                    </td>

                </tr>


                <tr>
                    <td>
                        Polygons generated
                    </td>

                    <td class="text-right">

                        <code> {{  $ptpAnnotations->count() }} </code>
                    </td>

                </tr>

                @if ($ptpAnnotations->count() <= 0)
                    <tr>
                        <td colspan="2" class="text-muted">
                            No polygons could begenerated for this job.
                        </td>
                    </tr>
                @endif



                </tbody>
            </table>

            </div>


{{--             FINISH REVIEW--}}

            <button
                type="button"
                class="btn btn-success btn-block"
                style="margin-bottom: 10px;"
                @click="finishReviewPtp"
                @disabled($ptpJob->status !== "waiting-for-review")
                @if($ptpJob->status === "review-completed")
                    title="This job has already been reviewed"
                @elseif($ptpJob->status === "pending" || $ptpJob->status === "in-progress")
                    title="Please wait for the conversion job to finish"
                @endif
                >
                Finish review
            </button>



            <!--- JOB LSÖCHEN   --->

            @if ($ptpJob->status === "pending" || $ptpJob->status === "in-progress")
                <button class="btn btn-danger" type="button" title="The job cannot be deleted while the conversion is running" disabled> Delete this job </button>

            @elseif ($ptpJob->status === "review-completed")
                <button class="btn btn-danger" type="button" title="The job cannot be deleted after the review was finished" disabled> Delete this job </button>
            @else

                <div>
                    <button class="btn btn-danger"
                            @click="deletePtpJob"
                            type="submit">Delete this job</button>
                </div>
            @endif


        </div>



    </sidebar-tab>


    <sidebar-tab
        name="label"
        icon="tags"
        title="Label trees">

        {{--<p> IRGENDWAS </p>--}}



        <label-trees
            {{--:trees="dataLabelTrees"--}}
            :trees="labelTrees"
            :show-favourites="true"
            v-on:select="handleSelectedLabel"
            v-on:deselect="handleDeselectedLabel"
            v-on:clear="handleDeselectedLabel">
        </label-trees>


    </sidebar-tab>


</sidebar>



<style scoped>


  /*  .ptp-job-tab__bottom {

        margin-top: auto;
        padding-top: 1em;
        text-align: right;
        flex-shrink: 0;
    }

    .sidebar-tab__content--ptp {
        height: 100%;


        display:flex;
        flex-direction:column;
    }




    .ptp-job-tab__top {
        flex:1;
        min-height: 0;
        overflow-y: auto;
    }*/
</style>

