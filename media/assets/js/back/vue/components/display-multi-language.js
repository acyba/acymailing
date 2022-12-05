jQuery(function ($) {
    if (undefined === $.fn.select2) return;

    Vue.component('multi-language', {
        name: 'multi-language',
        props: {
            value: Object,
            place: String,
            currentlangue: String,
            languageforselect2: Object
        },
        mounted: function () {
            this.languageforselect2decode = JSON.parse(this.languageforselect2);
            this.testLangue = this.currentlangue;
        },
        data: () => {
            return {
                testLangue: '',
                languageforselect2decode: {}
            };
        },
        template: `<div class="cell auto" v-if="testLangue!=''">
                       <select2 class="margin-bottom-1" :name="value" v-model="testLangue" :options="languageforselect2decode"></select2>
                       <input @change="saveData" :placeholder="place" v-show="testLangue == index" v-for="(language, index) in languageforselect2decode" key="index" v-model="value[index]" :class="index" type="text">
                   </div>`,
        methods: {
            saveData() {
                this.$emit('input', this.value);
            }
        }
    });
});
