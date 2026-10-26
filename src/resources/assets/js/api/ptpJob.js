import { Resource } from '../import.js';

/*
export default Resource('api/v1/send-ptp-job{/id}');
*/

/*
export default Resource('api/v1/ptp-jobs{/id}', {}, {
    review: {
        method: "POST",
        url: "api/v1/ptp-jobs{/id}/review",
    },
}); TODO ! vielleicht besser so? und dann aufteilen nach starten, review und potenziell Job löschen

*/
export default Resource('api/v1/send-ptp-job{/id}', {}, {
    review: {
        method: "POST",
        url: "api/v1/ptp-jobs{/id}/review",
    },
});
