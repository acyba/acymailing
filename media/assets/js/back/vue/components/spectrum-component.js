jQuery(function($) {
    if (undefined === $.fn.select2) return;

    Vue.component('spectrum', {
        name: 'spectrum',
        template: '<div class="cell auto grid-x"><input @click="openSpectrum" type="text" class="cell medium-6 acym__forms__menu__options__input-color-disabled" readonly :value="value"><input type="text" :name="name"></div>',
        props: {
            'value': String,
            'name': String
        },
        mounted: function () {
            let vueComp = this;
            $('[name="' + this.name + '"]').spectrum({
                color: this.value,
                preferredFormat: 'hex',
                showButtons: false,
                showInput: true,
                change: function (color) {
                    vueComp.$emit('input', color.toHexString());
                }
            });
        },
        methods: {
            openSpectrum() {
                $('[name="' + this.name + '"]').next().trigger('click');
            }
        },
        destroyed: function () {
            $('[name="' + this.name + '"]').spectrum('destroy');
        }
    });
});
