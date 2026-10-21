<template>

    <!--- Conversion options --->
    <fieldset>
        <div class="form-group">
            <div class="radio">
                <label for="point_to_polygon_sam">
                    <input type="radio" id="include_converted_points_sam" name="converted_points_sam" value="included" v-model="convertedPointsSam">
                    <strong>Convert all points</strong>
                    <div class="help-block">
                        Converts all point annotations in this volume, including points that were successfully converted
                        in previous jobs. All existing permanent polygon annotations are kept.
                        Overall points to convert in this volume: {{ totalPointCount }}.
                    </div>
                </label>
            </div>
        </div>


        <div class="form-group">
            <div class="radio">
                <label for="point_to_polygon_sam">
                    <input type="radio" id="ignore_converted_points_sam" name="converted_points_sam" value="ignored" v-model="convertedPointsSam" checked>
                    <strong>Ignore already converted points</strong>
                    <div class="help-block">
                        Only converts the points which do not have an accepted permanent polygon. Previously failed
                        or rejected point conversions are processed again.
                        All existing permanent polygon annotations are kept.
                        Overall points to convert in this volume: {{  remainingPointCount  }}.
                    </div>

                </label>
            </div>
        </div>


        <div id="ptp-label-selector"></div> <!--TODO !-->

    </fieldset>


    <div>
        <p>
            <button
                class="btn btn-success pull-right"
                type="button"
                :title="ptpButtonTitle"
                :disabled="loading || null"
                @click="sendPtpRequest">

                <loader :active="loading"></loader>
                Create job
            </button>
        </p>
        <p v-if="isJobRunning" class="text-info">
            The job was submitted. You will be notified when it is finished.
        </p>
    </div>
</template>


<script>
import PtpJobApi from "./api/ptpJob.js";
import { handleErrorResponse } from "./import.js";
import { LoaderMixin } from "./import.js";
//labelselector?

export default {
    mixins: [LoaderMixin],
    data() {
        return {
            volumeId: biigle.$require("ptp.volumeId"),
            convertedPointsSam: "ignored",
            totalPointCount: biigle.$require("ptp.totalPointCount"),
            remainingPointCount: biigle.$require("ptp.remainingPointCount"),
            /*manualUrl: biigle.$require("ptp.ptpManualUrl"),*/
        };
    },

    computed: {
        ptpButtonTitle() {
            if (this.loading) {
                return "Another Point to Polygon conversion Job is already running on this volume!";
            }

            return "Run Point to Polygon conversion on this volume";
        },

        isJobRunning() {
            return biigle.$require("ptp.isRunning") === true;
        }
    },

    methods: {
       /* sendPtpRequest() {
            PtpJobApi.save({ id: this.volumeId }, {})
                .then(this.startLoading, (e) => {
                    window.location.reload();
                    this.finishLoading();
                    handleErrorResponse(e);
                });
        }*/
        sendPtpRequest() {

            this.startLoading();

            PtpJobApi.save({ id: this.volumeId }, {  converted_points_sam: this.convertedPointsSam})
                .then(() => {
                    window.location.reload();
                })
                .catch((e) => {
                    this.finishLoading();
                    handleErrorResponse(e);
                });
        },

    },

    created() {
        if (biigle.$require("ptp.isRunning") === true) {
            this.startLoading();
        }
    }
};
</script>
