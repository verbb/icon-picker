<template>
    <span :class="['icon-picker-font', icon.classes]">{{ icon.displayValue }}</span>
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
