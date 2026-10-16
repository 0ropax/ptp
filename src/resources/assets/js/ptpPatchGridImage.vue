<script>

import {ImageGridImage} from "./import.js";

export default {
    mixins: [
        ImageGridImage,
    ],

    computed: {
        selected() {
            return this.image.rejected;
        },
        overlayUrl() {
          /*  return this.image.overlayUrl;*/
            return `/ptp/patches/${this.image.source_annotation.image.uuid}/${this.image.id}/svg`;
        },

        locked() {
            return biigle.$require("ptp.jobStatus") === "review-completed";
        },



     /*   iconClass() {
            if (this.locked) {
                return 'fa-lock';
            }

            return 'fa-' + this.selectedIcon;
        },*/
    },

    methods: {
        getThumbnailUrl() {
            return `/ptp/patches/${this.image.source_annotation.image.uuid}/${this.image.id}`;
        },
    },


}
</script>

<template>


    <figure
        class="image-grid__image image-grid__image--largo"
        :class="classObject">

        <div v-if="locked" class="ptp-lock-icon">
            <i class="fas fa-lock">

            </i>
        </div>


        <div v-if="selected" class="ptp-rejected-icon">
            <i class="fas fa-times">

            </i>
        </div>



<!--
        <div v-if="showIcon" class="image-icon">
            <i class="fas" :class="iconClass"></i>
        </div>-->
        <img :src="srcUrl" @error="showEmptyImage" @click="toggleSelect">
        <img :src="overlayUrl" class="outlines">
<!--        <img v-if="overlayUrl"
             :src="overlayUrl" class="outlines">-->
        <!--            <div
                        v-if="pinnable || isPinned"
                        class="image-buttons-bottom"
                    >
                        <button
                            class="image-button image-button__pin"
                            @click="emitPin"
                            :title="pinTitle"
                        >
                            <span class="fa fa-thumbtack fa-fw"></span>
                        </button>
                    </div>
                    <div v-if="showAnnotationLink" class="image-buttons">
                        <a :href="showAnnotationLink" target="_blank" class="image-button"
                           title="Show the annotation in the annotation tool">
                            <span class="fa fa-external-link-square-alt fa-fw" aria-hidden="true"></span>
                        </a>
                    </div>-->
    </figure>

</template>


<style scoped>

.ptp-lock-icon {
    pointer-events:none;
    position: absolute;
    width:100%;
    display:flex;

}

.ptp-rejected-icon {
    font-size: 6rem;
    pointer-events:none;
    position: absolute;
    height: 100%;
    display:flex;
    align-items: center;

}


</style>
