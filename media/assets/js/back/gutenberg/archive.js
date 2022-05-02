const acym_archiveBlock = {
    el: '',
    useBlockProps: '',
    blockEditor: '',
    components: '',
    wpElement: '',
    lists: [],
    boolchoice: [],
    styleMultiselect: {
        'minHeight': '90px'
    },
    init: function (blocks, element, blockEditor, wpComponents) {
        this.initLists();
        this.initBool();
        this.wpElement = element;
        this.el = this.wpElement.createElement;
        this.useBlockProps = blockEditor.useBlockProps;
        this.blockEditor = blockEditor;
        this.components = wpComponents;
        this.registerBlock(blocks);
        this.initCategory(blocks);
    },
    initLists: function () {
        for (let [value, label] of Object.entries(acym_lists_archive)) {
            this.lists.push({
                value: value,
                label: label
            });
        }
    },
    initBool: function () {
        this.boolchoice = [
            {
                value: '1',
                label: ACYM_JS_TXT.ACYM_YES
            },
            {
                value: '0',
                label: ACYM_JS_TXT.ACYM_NO
            }
        ];
    },
    registerBlock: function (blocks) {
        const icon = this.el('span', {
            class: 'dashicon dashicons dashicons-archive',
            style: {
                color: '#2199e8'
            }
        });
        blocks.registerBlockType('acymailing/archive', {
            apiVersion: 2,
            title: ACYM_JS_TXT.ACYM_ACYMAILING_ARCHIVE_FORM,
            icon: icon,
            category: 'acymailing',
            edit: this.edit
        });
    },
    edit: function (props) {
        let blockProps = acym_archiveBlock.useBlockProps();
        let self = acym_archiveBlock;
        let {Fragment} = self.wpElement;
        let {PanelBody} = self.components;
        let {InspectorControls} = self.blockEditor;

        return self.el('div', blockProps, self.el(Fragment, {}, self.el(InspectorControls, {}, self.el(
            PanelBody,
            {
                title: ACYM_JS_TXT.ACYM_MAIN_OPTIONS,
                initialOpen: true
            },
            self.textEdit(ACYM_JS_TXT.ACYM_TITLE, props, 'title'),
            self.selectEdit(ACYM_JS_TXT.ACYM_VISIBLE_LISTS, props, 'lists', 'lists', true),
            self.selectEdit(ACYM_JS_TXT.ACYM_ARCHIVE_POPUP, props, 'popup', 'boolchoice'),
            self.selectEdit(ACYM_JS_TXT.ACYM_ARCHIVE_ONLY_USER_LIST, props, 'displayUserListOnly', 'boolchoice')
        ))), self.el(window.wp.serverSideRender, {
            block: 'acymailing/archive',
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
    acym_archiveBlock.init(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
});