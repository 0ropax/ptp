
<template>

    <div class="form-group">

        <label> Select all the labels you want to convert: </label>

        <div class="well well-sm label-trees__body">
            <label-trees
                :trees="dataLabelTrees"
                :multiselect="true"
                :allow-select-siblings="true"
                :allow-select-children="true">
            </label-trees>
        </div>


    </div>

</template>



<script>

import {  LabelTrees  } from "./import.js";

    export default {
        components: {
            LabelTrees,
        },

        data() {
            return {
                dataLabelTrees: biigle.$require("ptp.labelTrees"), //blade declare !! labelTrees
            };

        },
    };

</script>



<style scoped>

:deep(.label-trees__body) {
    max-height: 300px;
    overflow-y: auto;
}

</style>
