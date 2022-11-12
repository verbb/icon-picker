<template>
    <span :class="icon.displayValue"></span>
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
