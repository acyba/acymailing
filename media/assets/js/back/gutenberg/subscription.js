const acym_subscriptionBlock = {
    el: '',
    useBlockProps: '',
    blockEditor: '',
    components: '',
    wpElement: '',
    ajaxUrl: 'admin-ajax.php?action=acymailing_router&noheader=1',
    lists: [],
    fields: [],
    posts: [],
    listPlacement: [],
    displayMode: [],
    displayTextMode: [],
    unsubButton: [],
    replaceMessage: [],
    subscriberInfo: [],
    alignment: [],
    includeJavascript: [],
    styleMultiselect: {
        'minHeight': '90px'
    },
    init: function (blocks, element, blockEditor, wpComponents) {
        this.initValues();
        this.initLists();
        this.initFields();
        this.initPosts();
        this.wpElement = element;
        this.el = this.wpElement.createElement;
        this.useBlockProps = blockEditor.useBlockProps;
        this.blockEditor = blockEditor;
        this.components = wpComponents;
        this.registerBlock(blocks);
        this.initCategory(blocks);
    },
    initValues: function () {
        this.listPlacement = [
            {
                value: 'before',
                label: ACYM_JS_TXT.ACYM_BEFORE_FIELDS
            },
            {
                value: 'after',
                label: ACYM_JS_TXT.ACYM_AFTER_FIELDS
            }
        ];
        this.displayMode = [
            {
                value: 'inline',
                label: ACYM_JS_TXT.ACYM_MODE_HORIZONTAL
            },
            {
                value: 'vertical',
                label: ACYM_JS_TXT.ACYM_MODE_VERTICAL
            },
            {
                value: 'tableless',
                label: ACYM_JS_TXT.ACYM_MODE_TABLELESS
            }
        ];
        this.displayTextMode = [
            {
                value: '1',
                label: ACYM_JS_TXT.ACYM_TEXT_INSIDE
            },
            {
                value: '0',
                label: ACYM_JS_TXT.ACYM_TEXT_OUTSIDE
            }
        ];
        this.unsubButton = [
            {
                value: '0',
                label: ACYM_JS_TXT.ACYM_NO
            },
            {
                value: '1',
                label: ACYM_JS_TXT.ACYM_CONNECTED_USER_SUBSCRIBED
            },
            {
                value: '2',
                label: ACYM_JS_TXT.ACYM_ALWAYS
            }
        ];
        this.replaceMessage = [
            {
                value: 'replace',
                label: ACYM_JS_TXT.ACYM_SUCCESS_REPLACE
            },
            {
                value: 'replacetemp',
                label: ACYM_JS_TXT.ACYM_SUCCESS_REPLACE_TEMP
            },
            {
                value: 'toptemp',
                label: ACYM_JS_TXT.ACYM_SUCCESS_TOP_TEMP
            },
            {
                value: 'standard',
                label: ACYM_JS_TXT.ACYM_SUCCESS_STANDARD
            }
        ];
        this.subscriberInfo = [
            {
                value: '1',
                label: ACYM_JS_TXT.ACYM_YES
            },
            {
                value: '0',
                label: ACYM_JS_TXT.ACYM_NO
            }
        ];
        this.alignment = [
            {
                value: 'none',
                label: ACYM_JS_TXT.ACYM_DEFAULT
            },
            {
                value: 'left',
                label: ACYM_JS_TXT.ACYM_LEFT
            },
            {
                value: 'center',
                label: ACYM_JS_TXT.ACYM_CENTER
            },
            {
                value: 'right',
                label: ACYM_JS_TXT.ACYM_RIGHT
            }
        ];
        this.includeJavascript = [
            {
                value: 'header',
                label: ACYM_JS_TXT.ACYM_IN_HEADER
            },
            {
                value: 'module',
                label: ACYM_JS_TXT.ACYM_ON_THE_MODULE
            }
        ];
    },
    initLists: function () {
        for (let [value, label] of Object.entries(acym_lists)) {
            this.lists.push({
                value: value,
                label: label
            });
        }
    },
    initFields: function () {
        for (let [value, field] of Object.entries(acym_fields)) {
            this.fields.push({
                value: value,
                label: field.name
            });
        }
    },
    initPosts: function () {
        acym_posts.map(posts => {
            posts.map(post => {
                this.posts.push({
                    value: post[0],
                    label: post[1]
                });
            });
        });
    },
    registerBlock: function (blocks) {
        const icon = this.el('span', {
            class: 'dashicon dashicons dashicons-email',
            style: {
                color: '#2199e8'
            }
        });
        blocks.registerBlockType('acymailing/subscription-form', {
            apiVersion: 2,
            title: ACYM_JS_TXT.ACYM_ACYMAILING_SUBSCRIPTION_FORM,
            icon: icon,
            category: 'acymailing',
            edit: this.edit
        });
    },
    edit: function (props) {
        let blockProps = acym_subscriptionBlock.useBlockProps();
        let self = acym_subscriptionBlock;
        let {Fragment} = self.wpElement;

        let {PanelBody} = self.components;

        let {InspectorControls} = self.blockEditor;

        const allLanguages = Object.keys(props.attributes).join().match(/(subtext_[a-z]{2,3}-[a-z]{2,3})/gmi);
        const allLanguagesLogged = Object.keys(props.attributes).join().match(/(subtextlogged_[a-z]{2,3}-[a-z]{2,3})/gmi);
        const allConfirmation = Object.keys(props.attributes).join().match(/(confirmation_message_[a-z]{2,3}-[a-z]{2,3})/gmi);
        let subTable = [];
        let confirmationTable = [];

        if (allLanguages != null) {
            let langueShort = '';
            allLanguages.forEach((langues) => {
                langueShort = langues.replace('subtext_', '');
                subTable.push(self.textEdit(`${ACYM_JS_TXT.ACYM_SUBSCRIBE_TEXT} ${langueShort}`, props, langues));
            });
            allLanguagesLogged.forEach((langues) => {
                langueShort = langues.replace('subtextlogged_', '');
                subTable.push(self.textEdit(`${ACYM_JS_TXT.ACYM_SUBSCRIBE_TEXT_LOGGED_IN} ${langueShort}`, props, langues));
            });
            allConfirmation.forEach((langues) => {
                langueShort = langues.replace('confirmation_message_', '');
                confirmationTable.push(self.textEdit(`${ACYM_JS_TXT.ACYM_CONFIRMATION_MESSAGE} ${langueShort}`, props, langues));
            });
        } else {
            subTable.push(self.textEdit(ACYM_JS_TXT.ACYM_SUBSCRIBE_TEXT, props, 'subtext'));
            subTable.push(self.textEdit(ACYM_JS_TXT.ACYM_SUBSCRIBE_TEXT_LOGGED_IN, props, 'subtextlogged'));
            confirmationTable.push(self.textEdit(ACYM_JS_TXT.ACYM_CONFIRMATION_MESSAGE, props, 'confirmation_message'));
        }
        return self.el('div', blockProps, self.el(Fragment, {}, self.el(InspectorControls, {}, self.el(PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_MAIN_OPTIONS,
                initialOpen: true
            },
            self.textEdit(ACYM_JS_TXT.ACYM_TITLE, props, 'title'),
            self.selectEdit(ACYM_JS_TXT.ACYM_DISPLAY_MODE, props, 'mode', 'displayMode'),
            ...subTable
        ), self.el(PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_LISTS_OPTIONS,
                initialOpen: true
            },
            self.selectEdit(ACYM_JS_TXT.ACYM_AUTO_SUBSCRIBE_TO, props, 'hiddenlists', 'lists', true),
            self.selectEdit(ACYM_JS_TXT.ACYM_DISPLAYED_LISTS, props, 'displists', 'lists', true),
            self.selectEdit(ACYM_JS_TXT.ACYM_LISTS_CHECKED_DEFAULT, props, 'listschecked', 'lists', true),
            self.selectEdit(ACYM_JS_TXT.ACYM_DISPLAY_LISTS, props, 'listposition', 'listPlacement')
        ), self.el(PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_FIELDS_OPTIONS,
                initialOpen: false
            },
            self.selectEdit(ACYM_JS_TXT.ACYM_FIELDS_TO_DISPLAY, props, 'fields', 'fields', true),
            self.selectEdit(ACYM_JS_TXT.ACYM_TEXT_MODE, props, 'textmode', 'displayTextMode'),
            self.selectEdit(ACYM_JS_TXT.ACYM_FORM_AUTOFILL_ID, props, 'userinfo', 'subscriberInfo')
        ), self.el(PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_TERMS_POLICY_OPTIONS,
                initialOpen: false
            },
            self.selectEdit(ACYM_JS_TXT.ACYM_TERMS_CONDITIONS, props, 'termscontent', 'posts'),
            self.selectEdit(ACYM_JS_TXT.ACYM_PRIVACY_POLICY, props, 'privacypolicy', 'posts')
        ), self.el(PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_SUBSCRIBE_OPTIONS,
                initialOpen: false
            },
            self.selectEdit(ACYM_JS_TXT.ACYM_SUCCESS_MODE, props, 'successmode', 'replaceMessage'),
            ...confirmationTable,
            self.textEdit(ACYM_JS_TXT.ACYM_REDIRECT_LINK, props, 'redirect')
        ), self.el(PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_UNSUBSCRIBE_OPTIONS,
                initialOpen: false
            },
            self.selectEdit(ACYM_JS_TXT.ACYM_DISPLAY_UNSUB_BUTTON, props, 'unsub', 'unsubButton'),
            self.textEdit(ACYM_JS_TXT.ACYM_UNSUBSCRIBE_TEXT, props, 'unsubtext'),
            self.textEdit(ACYM_JS_TXT.ACYM_REDIRECT_LINK_UNSUB, props, 'unsubredirect')
        ), self.el(PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_ADVANCED_OPTIONS,
                initialOpen: false
            },
            self.textEdit(ACYM_JS_TXT.ACYM_INTRO_TEXT, props, 'introtext'),
            self.textEdit(ACYM_JS_TXT.ACYM_POST_TEXT, props, 'posttext'),
            self.selectEdit(ACYM_JS_TXT.ACYM_ALIGNMENT, props, 'alignment', 'alignment'),
            self.selectEdit(ACYM_JS_TXT.ACYM_MODULE_JS, props, 'includejs', 'includeJavascript'),
            self.textEdit(ACYM_JS_TXT.ACYM_SOURCE, props, 'source')
        ))), self.el(window.wp.serverSideRender, {
            block: 'acymailing/subscription-form',
            attributes: props.attributes
        }));
    },
    textEdit: function (label, props, attribute, style = {'width': '100%'}) {
        let {TextControl} = this.components;

        return this.el(TextControl, {
            label: label,
            onChange: (value) => {
                let newAttribute = {};
                newAttribute[attribute] = value;
                props.setAttributes(newAttribute);
            },
            value: props.attributes[attribute],
            style: style
        });
    },
    selectEdit: function (label, props, attribute, optionName, multiple = false, style = {}) {
        let {SelectControl} = this.components;

        if (multiple) style = this.styleMultiselect;

        return this.el(SelectControl, {
            multiple: multiple,
            label: label,
            onChange: (value) => {
                let newAttribute = {};
                newAttribute[attribute] = value;
                props.setAttributes(newAttribute);
            },
            style: style,
            value: props.attributes[attribute],
            options: this[optionName]
        });
    },
    initCategory: function (blocks) {
        const acyIconSVG = this.el('svg', {
            width: 20,
            height: 20,
            viewBox: '0 0 1024 1024'
        }, this.el('path', {
            d: 'M553.074 174.168c-12.201 7.319-26.84 10.573-40.668 10.573s-27.655-3.253-40.668-10.573l-242.376-139.081-229.362 132.575v732.011l254.576 143.963v-430.26l219.602 124.442c11.388 6.507 24.401 9.76 37.415 9.76 0 0 0 0 0 0s0 0 0 0c12.201 0 25.214-3.253 36.6-9.76l221.23-124.442v430.26l254.576-144.775v-732.011l-229.362-131.762-241.563 139.081zM491.261 701.215l-217.164-122.815-61.001-34.161v430.26l-173.243-97.601v-662.063l422.94 245.629c0 0 0 0 0 0 8.947 4.881 17.895 8.135 27.655 10.573v230.178zM983.334 876.086l-173.243 98.416v-431.073l-61.001 34.161-217.164 122.815v-229.362c9.76-2.441 18.707-5.694 27.655-10.573l423.753-246.444v662.063zM539.246 425.493c-17.080 9.76-38.227 9.76-55.307 0 0 0 0 0 0 0l-422.94-245.629 168.362-97.601 222.043 127.696c18.707 10.573 39.853 16.267 61.001 16.267s42.294-5.694 61.001-16.267l222.043-127.696 168.362 97.601-424.566 245.629z',
            fill: '#2199e8'
        }));
        blocks.updateCategory('acymailing', {icon: acyIconSVG});
    }
};

window.addEventListener('DOMContentLoaded', () => {
    acym_subscriptionBlock.init(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
});
