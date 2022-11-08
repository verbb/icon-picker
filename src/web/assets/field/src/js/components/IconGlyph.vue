<template>
    <span :class="['icon-picker-font', 'font-face-' + icon.name]" v-html="icon.url"></span>
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
