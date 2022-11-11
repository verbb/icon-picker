<template>
    <svg v-html="icon.displayValue" />
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
