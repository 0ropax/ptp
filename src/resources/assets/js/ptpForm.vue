<template>

    <!--- Conversion options --->
    <fieldset>
        <div class="form-group">
            <div class="radio">
                <label>
                    <input type="radio"
                           name="converted_points_sam"
                           value="included"
                           v-model="convertedPointsSam">

                    <strong>Convert all points</strong>
                    <span class="help-block">
                        Converts all point annotations in this volume, including points that were successfully converted
                        in previous jobs. All existing permanent polygon annotations are kept.
                        Overall points to convert in this volume: {{ totalPointCount }}.
                    </span>
                </label>
            </div>
        </div>


        <div class="form-group">
            <div class="radio">
                <label>
                    <input type="radio"
                           name="converted_points_sam"
                           value="ignored"
                           v-model="convertedPointsSam"
                           checked>
                    <strong>Ignore already converted points</strong>
                    <span class="help-block">
                        Only converts the points which do not have an accepted permanent polygon. Previously failed
                        or rejected point conversions are processed again.
                        All existing permanent polygon annotations are kept.
                        Overall points to convert in this volume: {{  remainingPointCount  }}.
                    </span>

                </label>
            </div>
        </div>
    </fieldset>


    <!--- Create Job Button --->
    <div>
        <p>
            <button
                class="btn btn-success pull-right"
                type="button"
                :title="ptpButtonTitle"
                :disabled="loading || !userIsNotGuest"
                @click="sendPtpRequest">

                <loader :active="loading"></loader>
                Create job
            </button>
        </p>
        <p v-if="isJobRunning" class="text-info">
            The job was submitted. You will be notified when it is finished.
        </p>
        <p v-if="!userIsNotGuest" class="text-muted">
            You don't have permission to start a PTP conversion job for this volume.
        </p>
    </div>

</template>


<script>
import PtpJobApi from "./api/ptpJob.js";
import {  handleErrorResponse, LoaderMixin  } from "./import.js";

export default {

    mixins: [LoaderMixin],

    data() {
        return {
            volumeId: biigle.$require("ptp.volumeId"),
            convertedPointsSam: "ignored",
            totalPointCount: biigle.$require("ptp.totalPointCount"),
            remainingPointCount: biigle.$require("ptp.remainingPointCount"),
            userIsGuest: biigle.$require("ptp.userIsNotGuest"),
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
    },

    mounted() {

        const message = sessionStorage.getItem("ptpJobDeleted");

        if (message) {
            biigle.$require("messages").success(message);
            sessionStorage.removeItem("ptpJobDeleted");
        }


    },
};
</script>
