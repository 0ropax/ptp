import {Sidebar,SidebarTab, LabelTrees} from "./import.js";

import PtpPatchGrid from "./ptpPatchGrid.vue";
import PtpJob from "./api/ptpJob.js";

export default {
    components: {
        sidebar: Sidebar,
        sidebarTab: SidebarTab,
        ptpPatchGrid: PtpPatchGrid,
        labelTrees: LabelTrees,
    },

    data() {
        return {
            jobId: null,
            volumeId:null,
            annotations: [],
            jobStatus: null,
            selectedLabel: null,
            labelTrees: [],
        };
    },

    created() {
        this.jobId = biigle.$require("ptp.jobId");
        this.volumeId = biigle.$require("ptp.volumeId");
        this.annotations = biigle.$require("ptp.annotations");
        this.jobStatus = biigle.$require("ptp.jobStatus");
        this.labelTrees = biigle.$require("ptp.labelTrees");

        if (this.jobStatus === "review-completed") {
            this.annotations.forEach(annotation => {
               /* console.log("PTP_ANNOTATIONS", this.annotations);
                console.log("PTP_COUNT", this.annotations.length);*/
                annotation.rejected = annotation.annotation_id === null;
            })
        }
    },

    methods: {
        handleSelect(annotation) {

            if (this.locked) {
                return;
            }

            annotation.rejected = !annotation.rejected;

            //PATCH api / v1/ptp-annotations/{annoi,id}
        },

        finishReviewPtp() {
            const rejectedIds = [];

            for (const annotation of this.annotations) {
                if (annotation.rejected === true) {
                    rejectedIds.push(annotation.id);
                }
            }
       /*     console.log(rejectedIds);*/

            PtpJob.review( { id: this.jobId  }, {  rejected: rejectedIds  })
                .then((response) => {

                    const result = response.body;
                    const messages = [`${  result.accepted  } polygons accepted.`]

                    if (result.skipped_deleted > 0 ){
                        messages.push(`${  result.skipped_deleted  } skipped because source anno was deleted.`);
                    }

                    if (result.skipped_unlabeled > 0) {
                        messages.push(`${  result.skipped_unlabeled  } skipped because source anno has no label.`);
                    }

                    window.alert(messages.join("\n"));
                    window.location.reload();
            });


        },

        handleSelectedLabel(label) {
            this.selectedLabel = label;
        },

        handleDeselectedLabel() {
            this.selectedLabel = null;
        },

        /*Oben das array noch wegschicen TODO !*/
    },

    computed: {
        locked() {
            return this.jobStatus === "review-completed";
        },

        filteredAnnotations() {
            if (!this.selectedLabel) {
                return this.annotations;
            }

            const filtered = [];

            for (const annotation of this.annotations) {
                if (annotation.label_id === this.selectedLabel.id) {
                    filtered.push(annotation);
                }
            }

            return filtered;
        }
    },


};


