import { Resource } from '../import.js';


export default Resource('api/v1/send-ptp-job{/id}', {}, {
    review: {
        method: "POST",
        url: "api/v1/ptp-jobs{/id}/review",
    },
});
