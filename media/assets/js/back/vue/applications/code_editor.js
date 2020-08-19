jQuery(document).ready(function ($) {

    if ($('#acym__wysid__edit__button').length > 0) {

        Vue.component('vue-prism-editor', VuePrismEditor);
        new Vue({
            el: '#acym__wysid__editor__source',
            data() {
                return {
                    code: '',
                    codeBase: '',
                    language: 'html',
                    optionIndent: {
                        indent_size: 2, // space_in_empty_paren: true,
                        wrap_line_length: '120'
                    },
                    typingTimer: '',
                    doneTypingInterval: 1000
                };
            },
            mounted() {
                let contentBlock = document.getElementById('acym__wysid__block__html__content');
                contentBlock.addEventListener('editor_change', () => {
                    //in case the cdn is not working
                    this.changeCodeValue(undefined === html_beautify ? contentBlock.value : html_beautify(contentBlock.value, this.optionIndent));
                });
            },
            methods: {
                changeCodeValue(newValue) {
                    this.code = newValue;
                    this.codeBase = newValue;
                },
                revert() {
                    this.applyModification(this.codeBase);
                    $('#acym__wysid__editor__source, #acym__wysid__right-toolbar__overlay').removeClass('acym__wysid__visible');
                },
                keep() {
                    $('#acym__wysid__editor__source, #acym__wysid__right-toolbar__overlay').removeClass('acym__wysid__visible');
                },
                applyModification(newCodeValue) {
                    let $focusedSelector = $('.acym__wysid__row__selector--focus');
                    $focusedSelector.closest('.acym__wysid__row__element').find('> tbody').html(newCodeValue);
                    $focusedSelector.css('height', $focusedSelector.closest('.acym__wysid__row__element').css('height'));
                }
            },
            watch: {
                code(value) {
                    clearTimeout(this.typingTimer);
                    this.typingTimer = setTimeout(() => {
                        this.applyModification(value);
                    }, this.doneTypingInterval);
                }
            }
        });
    }

});
