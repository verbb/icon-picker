export const hideOnEsc = {
    name: 'hideOnEsc',
    defaultValue: true,
    fn(instance) {
        function onKeyDown(event) {
            if (event.keyCode === 27) {
                instance.hide();

                document.activeElement.blur();
            }
        }

        return {
            onShow() {
                document.addEventListener('keydown', onKeyDown);
            },

            onHide() {
                document.removeEventListener('keydown', onKeyDown);
            },
        };
    },
};
