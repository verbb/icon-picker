<template>
    <span :class="['icon-picker-font', 'font-face-' + icon.iconSet]" v-html="icon.displayValue"></span>
</template>

<script>
export default {
    props: {
        icon: {
            type: Object,
            default: () => {},
        },
    },
};
</script>
