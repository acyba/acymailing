document.addEventListener('DOMContentLoaded', function () {
    const dtextPicker = document.getElementById('acym__dtext__picker');
    const dtextCodeContainer = document.getElementById('acym__dtext__picker__modal__code');
    const dtextOpenButton = document.getElementById('acym__dtext__button');
    const dtextEditorId = dtextOpenButton.getAttribute('data-acym-editor');
    const dtextEditorSelectionId = dtextOpenButton.getAttribute('data-acym-selection');
    const dtextInsertButton = document.getElementById('acym__dtext__picker__modal__insert');
    const wrapper = document.querySelector('#acym_wrapper');

    let acymSelectionStartInEditor = null;
    let acymSelectionEndInEditor = null;

    initDtextOpenButton();
    handleDtextPickerClose();
    handleDtextInsertion();
    initDtextPicker();
    initRangeSelection();

    function initDtextOpenButton() {
        dtextOpenButton.addEventListener('click', function (event) {
            if (!dtextEditorId && (typeof tinymce === 'undefined' || tinymce.activeEditor === null)) {
                dtextInsertButton.innerText = ACYM_JS_TXT.ACYM_COPY_CODE;
            }

            dtextPicker.classList.toggle('is-hidden');
        });

        if (!dtextEditorId && ACYM_CMS === 'joomla') {
            const contentDiv = document.getElementById('acym_content');
            if (contentDiv) {
                contentDiv.appendChild(dtextOpenButton);
            } else {
                wrapper.appendChild(dtextOpenButton);
            }
        }
    }

    function handleDtextPickerClose() {
        dtextPicker.addEventListener('click', function (event) {
            if (![
                'acym__dtext__picker',
                'acym__dtext__picker__modal__close'
            ].includes(event.target.id)) {
                return;
            }

            dtextPicker.classList.toggle('is-hidden');
        });
    }

    function handleDtextInsertion() {
        dtextInsertButton.addEventListener('click', function () {
            if (dtextEditorId) {
                insertContentInElementWithId(dtextEditorId, dtextCodeContainer.value);
                dtextPicker.classList.toggle('is-hidden');
            } else {
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor !== null) {
                    tinymce.activeEditor.insertContent(dtextCodeContainer.value);
                    dtextPicker.classList.toggle('is-hidden');
                } else {
                    const dtextCodeContainer = document.getElementById('acym__dtext__picker__modal__code');
                    dtextCodeContainer.select();
                    navigator.clipboard.writeText(dtextCodeContainer.value);
                }
            }
        });
    }

    function initDtextPicker() {
        // Move the modal to the wrapper for CSS to be applied
        wrapper.appendChild(dtextPicker);
    }

    function initRangeSelection() {
        document.addEventListener('selectionchange', function () {
            const selection = window.getSelection();
            if (!dtextEditorSelectionId || selection.anchorNode.id !== dtextEditorSelectionId) {
                return;
            }

            const editor = document.getElementById(dtextEditorId);
            acymSelectionStartInEditor = editor.selectionStart;
            acymSelectionEndInEditor = editor.selectionEnd;
        });
    }

    function insertContentInElementWithId(elementId, textToInsert) {
        const editor = document.getElementById(elementId);

        if (acymSelectionStartInEditor === null) {
            editor.value = editor.value + textToInsert;
        } else {
            const contentBeforeSelection = editor.value.slice(0, acymSelectionStartInEditor);
            const contentAfterSelection = editor.value.slice(acymSelectionEndInEditor, editor.value.length);

            editor.value = contentBeforeSelection + textToInsert + contentAfterSelection;

            const caretPosition = acymSelectionStartInEditor + textToInsert.length;
            if (editor.createTextRange) {
                const range = editor.createTextRange();
                range.move('character', caretPosition);
                range.select();
            } else {
                if (editor.selectionStart) {
                    editor.focus();
                    editor.setSelectionRange(caretPosition, caretPosition);
                } else {
                    editor.focus();
                }
            }

            acymSelectionStartInEditor = null;
            acymSelectionEndInEditor = null;

            editor.dispatchEvent(new Event('keyup'));
        }
    }
});

function setTag(tagvalue, element) {
    const dtextCodeContainer = document.getElementById('acym__dtext__picker__modal__code');
    dtextCodeContainer.value = tagvalue;
}
