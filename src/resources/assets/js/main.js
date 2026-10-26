import PtpForm from './ptpForm.vue';
import {VolumeShowPlugins} from './import.js';
import PtpLabelTreeSelector from "./ptpLabelTreeSelector.vue";
import PtpReview from "./ptpReview.js";


biigle.$mount("ptp-job-form", PtpForm);

biigle.$mount("ptp-label-selector", PtpLabelTreeSelector);

biigle.$mount("ptp-review", PtpReview);
