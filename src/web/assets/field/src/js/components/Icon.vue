<template>
    <template v-if="item.type === 'svg'">
        <div v-html="item.displayValue"></div>
    </template>

    <template v-if="item.type === 'sprite'">
        <svg viewBox="0 0 1000 1000"><use :xlink:href="'#' + item.displayValue" /></svg>
    </template>

    <template v-if="item.type === 'glyph'">
        <span :class="['ipui-font', 'font-face-' + item.iconSet]" v-html="item.displayValue"></span>
    </template>

    <template v-if="item.type === 'css'">
        <span :[cssAttribute]="item.displayValue"></span>
    </template>
</template>

<script>

export default {
    props: {
        item: {
            type: Object,
            default: () => {},
        },

        index: {
            type: Number,
            default: 0,
        },

        cssAttribute: {
            type: String,
            default: 'class',
        },
    },
};

</script>
