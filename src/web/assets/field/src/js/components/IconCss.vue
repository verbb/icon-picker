<template>
    <span :class="['icon-picker-font', icon.classes]">{{ icon.url }}</span>
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
