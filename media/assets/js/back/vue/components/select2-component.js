jQuery(document).ready(function ($) {
    if (undefined === $.fn.select2) return;

    $.fn.select2.defaults.set('theme', 'foundation');
    Vue.component('select2', {
        template: '<div class="cell"><select :name="name">' + '<option v-for="(option, key) in options" :value="key">{{ option }}</option>' + '</select></div>',
        props: [
            'options',
            'value',
            'name',
        ],
        mounted: function () {
            let vueComp = this;
            $('[name="' + this.name + '"]')
                // init select2
                .select2({
                    theme: 'foundation',
                    width: '100%',
                })
                .val(this.value)
                .trigger('change')
                // emit event on change.
                .on('change', function () {
                    //it allows to tells to the higher application that the value changed
                    vueComp.$emit('input', this.value);
                });
        },
        watch: {
            value: function (value) {
                // update value
                $('[name="' + this.name + '"]').val(value).trigger('change');
            },
            options: function (options) {
                // update options
                $('[name="' + this.name + '"]').select2({data: options});
            },
        },
        destroyed: function () {
            $('[name="' + this.name + '"]').off().select2('destroy');
        },
    });
});
