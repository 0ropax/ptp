<template>
    <div class="ptp-container">
        <p>
            <button
                class="btn btn-success btn-block"
                type="button"
                :title="ptpButtonTitle"
                :disabled="loading || null"
                @click="sendPtpRequest"
                >
                <loader :active="loading"></loader> Create job
            </button>
        </p>
        <p v-if="loading" class="text-info">
            The job was submitted. You will be notified when it is finished.
        </p>
    </div>
</template>
<script>
import PtpJobApi from "./api/ptpJob.js";
import { handleErrorResponse } from "./import.js";
import { LoaderMixin } from "./import.js";

export default {
    mixins: [LoaderMixin],
    data() {
        return {
            volumeId: biigle.$require("ptp.volumeId"),
            /*manualUrl: biigle.$require("ptp.ptpManualUrl"),*/
        };
    },

    computed: {
        ptpButtonTitle() {
            if (this.loading) {
                return "Another Point to Polygon conversion Job is already running on this volume!";
            }

            return "Run Point to Polygon conversion on this volume";
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

            PtpJobApi.save({ id: this.volumeId }, {})
                .then(() => {
                    window.location.reload();
                })
                .catch((e) => {
                    this.finishLoading();
                    handleErrorResponse(e);
                });
        }

    },

    created() {
        if (biigle.$require("ptp.isRunning") === true) {
            this.startLoading();
        }
    }
};
</script>
