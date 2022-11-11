<template>
    <svg viewBox="0 0 1000 1000"><use :xlink:href="'#' + icon.displayValue" /></svg>
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
