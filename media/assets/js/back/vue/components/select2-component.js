jQuery(function ($) {
    if (undefined === $.fn.select2) return;

    $.fn.select2.defaults.set('theme', 'foundation');

    Vue.component('select2multiple', {
        name: 'select2multiple',
        template: `<div class="cell">
                    <select multiple :name="name">
                        <option v-if="allOptionExist" :value="'all'">{{ options.all }}</option>
                        <option v-if="allOptionExist" disabled>-----------------------------</option>
                        <option v-for="(option, key) in options" :value="key" v-if="key !== 'all'">{{ option }}</option>
                    </select>
                   </div>`,
        props: {
            'options': Object,
            'value': Array,
            'name': String
        },
        data: () => {
            return {
                oldSelected: [],
                allOptionExist: false
            };
        },
        mounted: function () {
            let vueComp = this;
            this.oldSelected = this.value;
            this.allOptionExist = Object.keys(this.options).indexOf('all') !== -1;

            setTimeout(() => {
                $('[name="' + this.name + '"]')
                    .select2({
                        theme: 'foundation',
                        width: '100%'
                    })
                    .val(this.value)
                    .trigger('change')
                    .on('change', function () {
                        // It allows to tell to the higher application that the value changed
                        let previousValues = vueComp.oldSelected;
                        if (previousValues === null) previousValues = [];
                        let selectedValues = $(this).val();
                        if (selectedValues === null) selectedValues = [];

                        if (Object.keys(vueComp.options).filter(option => option === 'all').length > 0 && !acym_helper.empty(selectedValues)) {
                            if (selectedValues.indexOf('all') !== -1) {
                                if (previousValues.indexOf('all') === -1) {
                                    $(this).val(['all']);
                                } else {
                                    let newValues = selectedValues.filter(page => page !== 'all');
                                    $(this).val(newValues === null ? [] : newValues);
                                }
                            }
                        }

                        let newValues = $(this).val();
                        if (newValues === null) newValues = [];
                        vueComp.oldSelected = newValues;
                        vueComp.$emit('input', newValues);
                    });
            }, 100);
        },
        watch: {
            options: function (options) {
                // update options
                $('[name="' + this.name + '"]').select2({data: options});
            }
        },
        destroyed: function () {
            $('[name="' + this.name + '"]').off().select2('destroy');
        }
    });

    Vue.component('select2', {
        name: 'select2',
        template: '<div class="cell"><select :name="name">' + '<option v-for="(option, key) in options" :value="key">{{ option }}</option>' + '</select></div>',
        props: [
            'options',
            'value',
            'name'
        ],
        mounted: function () {
            let vueComp = this;
            $('[name="' + this.name + '"]')
                // init select2
                .select2({
                    theme: 'foundation',
                    width: '100%'
                })
                .val(this.value)
                .trigger('change')
                // emit event on change.
                .on('change', function () {
                    //it allows to tells to the higher application that the value changed
                    vueComp.$emit('input', this.value);
                });
            const event = new Event(`select2-${this.name}`);
            window.dispatchEvent(event);
        },
        watch: {
            options: function (options) {
                // update options
                $('[name="' + this.name + '"]').select2({data: options});
            }
        },
        destroyed: function () {
            $('[name="' + this.name + '"]').off().select2('destroy');
        }
    });

    Vue.component('select2ajax', {
        name: 'select2ajax',
        template: '<div class="cell"><select :name="name">'
                  + '<option v-for="(option, key) in options" :value="option.id">{{ option.text }}</option>'
                  + '</select></div>',
        props: [
            'value',
            'name',
            'urlselected',
            'ctrl',
            'task'
        ],
        data: () => {
            return {
                options: []
            };
        },
        mounted: function () {
            let vueComp = this;
            $.get(ACYM_AJAX_URL + vueComp.urlselected + this.value, (data) => {
                data = acym_helper.parseJson(data);
                let $selectAjax = $('[name="' + this.name + '"]');
                if (!acym_helper.empty(data)) {
                    let newOption = new Option(data.title, data.id, false, false);
                    $selectAjax.append(newOption);
                }

                // init select2
                $selectAjax.select2({
                    ajax: {
                        url: ACYM_AJAX_URL,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                searchedterm: params.term,
                                ctrl: vueComp.ctrl,
                                task: vueComp.task
                            };
                        },
                        processResults: function (data) {
                            vueComp.options = [];
                            if (data) {
                                jQuery.each(data, function (index, text) {
                                    vueComp.options.push({
                                        id: text[0],
                                        text: text[1]
                                    });
                                });
                            }
                            return {
                                results: vueComp.options
                            };
                        }
                    },
                    theme: 'foundation',
                    width: '100%',
                    minimumInputLength: 3,
                    allowClear: true,
                    placeholder: '- - -'
                })
                           .val(this.value)
                    // emit event on change.
                           .on('change', function () {
                               //it allows to tells to the higher application that the value changed
                               vueComp.$emit('input', this.value);
                           });
            });
        },
        destroyed: function () {
            $('[name="' + this.name + '"]').off().select2('destroy');
        }
    });
});
