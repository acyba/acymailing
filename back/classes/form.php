<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class FormClass extends acymClass
{
    const SUB_FORM_TYPE_SHORTCODE = 'shortcode';
    const SUB_FORM_TYPE_POPUP = 'popup';
    const SUB_FORM_TYPE_HEADER = 'header';
    const SUB_FORM_TYPE_FOOTER = 'footer';

    private $settings;

    public function __construct()
    {
        parent::__construct();

        $this->table = 'form';
        $this->pkey = 'id';

        $listClass = new ListClass();
        $lists = $listClass->getAllForSelect(false);

        $fieldClass = new FieldClass();
        $allFields = $fieldClass->getAll();
        $fields = [];
        foreach ($allFields as $field) {
            if ($field->id == 2 || intval($field->active) === 0) continue;
            $fields[$field->id] = acym_translation($field->name);
        }

        $this->settings = [
            'options' => [
                'display' => [
                    'display_action' => [
                        'label' => 'ACYM_DISPLAY_ON_CLICK',
                        'description' => 'ACYM_DISPLAY_ON_CLICK_DESC',
                        'type' => 'select',
                        'options' => [
                            'yes' => acym_translation('ACYM_YES'),
                            'no' => acym_translation('ACYM_NO'),
                        ],
                        'default' => 'no',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'button' => [
                        'label' => 'ACYM_BUTTON_ID',
                        'description' => 'ACYM_BUTTON_ID_DESC',
                        'type' => 'text',
                        'default' => '',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'delay' => [
                        'label' => 'ACYM_DELAY',
                        'description' => 'ACYM_DELAY_DESC',
                        'type' => 'number',
                        'unit' => acym_translation('ACYM_SECONDS'),
                        'default' => 0,
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'scroll' => [
                        'label' => 'ACYM_SCROLL',
                        'description' => 'ACYM_SCROLL_DESC',
                        'type' => 'number',
                        'unit' => '%',
                        'max' => 100,
                        'default' => 0,
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                ],
                'lists' => [
                    'automatic_subscribe' => [
                        'label' => 'ACYM_AUTO_SUBSCRIBE_TO',
                        'description' => 'ACYM_AUTO_SUBSCRIBE_TO_DESC',
                        'type' => 'multiselect',
                        'options' => $lists,
                        'default' => [],
                    ],
                    'displayed' => [
                        'label' => 'ACYM_DISPLAYED_LISTS',
                        'description' => 'ACYM_DISPLAYED_LISTS_DESC',
                        'type' => 'multiselect',
                        'options' => $lists,
                        'default' => [],
                    ],
                    'checked' => [
                        'label' => 'ACYM_LISTS_CHECKED_DEFAULT',
                        'description' => 'ACYM_LISTS_CHECKED_DEFAULT_DESC',
                        'type' => 'multiselect',
                        'options' => $lists,
                        'default' => [],
                    ],
                    'display_position' => [
                        'label' => 'ACYM_DISPLAY_LISTS',
                        'type' => 'select',
                        'options' => [
                            'after' => acym_translation('ACYM_AFTER_FIELDS'),
                            'before' => acym_translation('ACYM_BEFORE_FIELDS'),
                        ],
                        'default' => 'after',
                    ],
                ],
                'fields' => [
                    'displayed' => [
                        'label' => 'ACYM_FIELDS_TO_DISPLAY',
                        'type' => 'multiselect',
                        'options' => $fields,
                        'default' => [],
                    ],
                    'display_mode' => [
                        'label' => 'ACYM_DISPLAY_FIELDS_LABEL',
                        'type' => 'select',
                        'options' => [
                            'inside' => acym_translation('ACYM_TEXT_INSIDE'),
                            'outside' => acym_translation('ACYM_TEXT_OUTSIDE'),
                        ],
                        'default' => 'inside',
                    ],
                ],
                'termspolicy' => [
                    'termscond' => [
                        'label' => 'ACYM_TERMS_CONDITIONS',
                        'type' => 'article',
                        'default' => 0,
                    ],
                    'privacy' => [
                        'label' => 'ACYM_PRIVACY_POLICY',
                        'type' => 'article',
                        'default' => 0,
                    ],
                ],
                'message' => [
                    'text' => [
                        'label' => 'ACYM_CUSTOM_MESSAGE_DISPLAY',
                        'type' => 'textarea',
                        'default' => '',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'position' => [
                        'label' => 'ACYM_MESSAGE_POSITION',
                        'type' => 'select',
                        'options' => [
                            'before-image' => acym_translation('ACYM_BEFORE_IMAGE'),
                            'before-fields' => acym_translation('ACYM_BEFORE_FIELDS'),
                            'before-lists' => acym_translation('ACYM_BEFORE_LISTS'),
                            'before-button' => acym_translation('ACYM_BEFORE_BUTTON'),
                        ],
                        'default' => 'before-fields',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'color' => [
                        'label' => 'ACYM_COLOR',
                        'type' => 'color',
                        'default' => '#000000',
                    ],
                ],
                'cookie' => [
                    'cookie_expiration' => [
                        'label' => 'ACYM_COOKIE_EXPIRATION',
                        'type' => 'number',
                        'min' => 1,
                        'unit' => acym_translation('ACYM_DAYS'),
                        'default' => 1,
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP, self::SUB_FORM_TYPE_HEADER, self::SUB_FORM_TYPE_FOOTER],
                    ],
                ],
                'redirection' => [
                    'after_subscription' => [
                        'label' => 'ACYM_AFTER_SUBSCRIPTION',
                        'description' => 'ACYM_REDIRECT_LINK_DESC',
                        'type' => 'text',
                        'default' => '',
                    ],
                    'confirmation_message' => [
                        'label' => 'ACYM_CONFIRMATION_MESSAGE',
                        'description' => 'ACYM_CONFIRMATION_MESSAGE_DESC',
                        'type' => 'text',
                        'default' => '',
                    ],
                ],
                'miscellaneous' => [
                    'js_loading' => [
                        'label' => 'ACYM_JS_LOADING',
                        'description' => 'ACYM_MODULE_JS_DESC',
                        'type' => 'select',
                        'options' => [
                            'form' => acym_translation('ACYM_IN_FORM'),
                            'head' => acym_translation('ACYM_WP_ENQUEUE_SCRIPT'),
                        ],
                        'default' => 'form',
                        'allowed_types' => [self::SUB_FORM_TYPE_SHORTCODE],
                    ],
                ],
            ],
            'styles' => [
                'image' => [
                    'url' => [
                        'label' => 'ACYM_CHOOSE_IMAGE',
                        'type' => 'media',
                        'default' => '',
                    ],
                    'size' => [
                        'label' => 'ACYM_SIZE',
                        'type' => 'dimensions',
                        'default' => ['width' => 100, 'height' => 100],
                    ],
                ],
                'button' => [
                    'background_color' => [
                        'label' => 'ACYM_BACKGROUND_COLOR',
                        'type' => 'color',
                        'default' => '#000000',
                    ],
                    'text_color' => [
                        'label' => 'ACYM_TEXT_COLOR',
                        'type' => 'color',
                        'default' => '#ffffff',
                    ],
                    'border_color' => [
                        'label' => 'ACYM_BORDER_COLOR',
                        'type' => 'color',
                        'default' => '#000000',
                    ],
                    'border_type' => [
                        'label' => 'ACYM_BORDER_TYPE',
                        'type' => 'select',
                        'options' => [
                            'solid' => acym_translation('ACYM_SOLID'),
                            'dotted' => acym_translation('ACYM_DOTTED'),
                            'dashed' => acym_translation('ACYM_DASHED'),
                            'double' => acym_translation('ACYM_DOUBLE'),
                            'groove' => acym_translation('ACYM_GROOVE'),
                            'ridge' => acym_translation('ACYM_RIDGE'),
                            'inset' => acym_translation('ACYM_INSET'),
                            'outset' => acym_translation('ACYM_OUTSET'),
                        ],
                        'default' => 'solid',
                    ],
                    'border_size' => [
                        'label' => 'ACYM_BORDER_SIZE',
                        'type' => 'number',
                        'default' => 0,
                    ],
                    'border_radius' => [
                        'label' => 'ACYM_RADIUS',
                        'type' => 'number',
                        'default' => 0,
                    ],
                    'size' => [
                        'label' => 'ACYM_SIZE',
                        'type' => 'dimensions',
                        'default' => ['width' => 20, 'height' => 10],
                    ],
                    'text' => [
                        'label' => 'ACYM_SUBSCRIBE_TEXT',
                        'type' => 'text',
                        'placeholder' => acym_translation('ACYM_SUBSCRIBE'),
                        'default' => '',
                    ],
                ],
                'style' => [
                    'position' => [
                        'label' => 'ACYM_DISPLAY_FIELDS_LABEL',
                        'type' => 'position',
                        'default' => 'image-top',
                        'allowed_types' => [self::SUB_FORM_TYPE_HEADER, self::SUB_FORM_TYPE_POPUP, self::SUB_FORM_TYPE_FOOTER],
                    ],
                    'background_color' => [
                        'label' => 'ACYM_BACKGROUND_COLOR',
                        'type' => 'color',
                        'default' => '#ffffff',
                    ],
                    'background_image' => [
                        'label' => 'ACYM_BACKGROUND_IMAGE',
                        'type' => 'media',
                        'default' => '',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'background_position' => [
                        'label' => 'ACYM_POSITION',
                        'type' => 'select',
                        'options' => [
                            'center' => acym_translation('ACYM_CENTER'),
                            'top' => acym_translation('ACYM_POSITION_TOP'),
                            'right' => acym_translation('ACYM_RIGHT'),
                            'right_bottom' => acym_translation('ACYM_POSITION_RIGHT_BOTTOM'),
                            'right_top' => acym_translation('ACYM_POSITION_RIGHT_TOP'),
                            'bottom' => acym_translation('ACYM_POSITION_BOTTOM'),
                            'left' => acym_translation('ACYM_LEFT'),
                            'left_top' => acym_translation('ACYM_POSITION_LEFT_TOP'),
                            'left_bottom' => acym_translation('ACYM_POSITION_LEFT_BOTTOM'),
                        ],
                        'default' => 'center',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'background_size' => [
                        'label' => 'ACYM_BACKGROUND_SIZE',
                        'type' => 'select',
                        'options' => [
                            'contain' => acym_translation('ACYM_BG_CONTAIN'),
                            'cover' => acym_translation('ACYM_BG_COVER'),
                        ],
                        'default' => 'contain',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'background_repeat' => [
                        'label' => 'ACYM_BACKGROUND_REPEAT',
                        'type' => 'select',
                        'options' => [
                            'repeat' => acym_translation('ACYM_YES'),
                            'no-repeat' => acym_translation('ACYM_NO'),
                        ],
                        'default' => 'no-repeat',
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'text_color' => [
                        'label' => 'ACYM_TEXT_COLOR',
                        'type' => 'color',
                        'default' => '#000000',
                    ],
                    'padding' => [
                        'label' => 'ACYM_PADDING',
                        'type' => 'dimensions',
                        'default' => ['width' => 20, 'height' => 20],
                        'allowed_types' => [self::SUB_FORM_TYPE_POPUP],
                    ],
                    'size' => [
                        'label' => 'ACYM_SIZE',
                        'type' => 'dimensions',
                        'units' => [
                            self::SUB_FORM_TYPE_HEADER => ['width' => '%', 'height' => 'px'],
                            self::SUB_FORM_TYPE_FOOTER => ['width' => '%', 'height' => 'px'],
                        ],
                        'default' => ['width' => 400, 'height' => 300],
                        'allowed_types' => [self::SUB_FORM_TYPE_HEADER, self::SUB_FORM_TYPE_SHORTCODE, self::SUB_FORM_TYPE_FOOTER],
                    ],
                ],
            ],
        ];

        if (acym_isMultilingual()) {
            $languageTexts = [];
            foreach (acym_getMultilingualLanguages() as $key => $languages) {
                $languageTexts[$key] = '';
            }
            unset($this->settings['options']['redirection']['confirmation_message']);
            $this->settings['options']['redirection']['langConfirm'] = [
                'label' => 'ACYM_CONFIRMATION_MESSAGE',
                'description' => 'ACYM_CONFIRMATION_MESSAGE_DESC',
                'type' => 'language',
                'default' => $languageTexts,
            ];

            unset($this->settings['styles']['button']['text']);
            $this->settings['styles']['button']['lang'] = [
                'label' => 'ACYM_SUBSCRIBE_TEXT',
                'type' => 'language',
                'default' => $languageTexts,
            ];
        }
    }

    private function getSectionTranslations(): array
    {
        return [
            'display' => 'ACYM_DISPLAY',
            'lists' => 'ACYM_LISTS',
            'fields' => 'ACYM_FIELDS',
            'termspolicy' => 'ACYM_ARTICLE',
            'cookie' => 'ACYM_COOKIE_SETTINGS',
            'redirection' => 'ACYM_REDIRECTIONS',
            'miscellaneous' => 'ACYM_MISCELLANEOUS',
            'message' => 'ACYM_MESSAGE',
            'image' => 'ACYM_IMAGE',
            'button' => 'ACYM_BUTTON',
            'style' => 'ACYM_STYLE',
        ];
    }

    public function getMatchingElements(array $settings = []): array
    {
        $query = 'SELECT form.* FROM #__acym_form AS form';
        $queryCount = 'SELECT COUNT(form.id) AS total, SUM(active) AS totalActive FROM #__acym_form AS form';

        if (!empty($settings['search'])) {
            $filters[] = 'name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['status'])) {
            $query .= empty($filters) ? ' WHERE ' : ' AND ';
            $query .= 'active = '.($settings['status'] == 'active' ? '1' : '0');
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY '.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        } else {
            $query .= ' ORDER BY id asc';
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']);
        $results['total'] = acym_loadObject($queryCount);

        return $results;
    }

    public function getTranslatedTypes(): array
    {
        return [
            self::SUB_FORM_TYPE_FOOTER => acym_translation('ACYM_FOOTER'),
            self::SUB_FORM_TYPE_HEADER => acym_translation('ACYM_HEADER'),
            self::SUB_FORM_TYPE_POPUP => acym_translation('ACYM_POPUP'),
            self::SUB_FORM_TYPE_SHORTCODE => acym_translation('ACYM_SHORTCODE'),
        ];
    }

    public function getOneById($id)
    {
        $form = acym_loadObject('SELECT * FROM #__acym_form WHERE id = '.intval($id));

        if (empty($form)) {
            return $form;
        }

        $form->pages = json_decode($form->pages, true);
        $form->settings = json_decode($form->settings, true);
        $form->display_languages = json_decode($form->display_languages, true);

        return $form;
    }

    public function getAllFormsToDisplay()
    {
        $forms = acym_loadObjectList('SELECT * FROM #__acym_form WHERE active = 1 AND type != '.acym_escapeDB(self::SUB_FORM_TYPE_SHORTCODE));
        foreach ($forms as $form) {
            $form->pages = json_decode($form->pages, true);
            $form->settings = json_decode($form->settings, true);
            $form->display_languages = json_decode($form->display_languages, true);
        }

        return $forms;
    }

    public function initEmptyForm($type)
    {
        $newForm = new \stdClass();
        $newForm->id = 0;
        $newForm->name = '';
        $newForm->creation_date = acym_date('now', 'Y-m-d H:i:s');
        $newForm->active = 1;
        $newForm->type = $type;
        $newForm->pages = ['all'];
        $newForm->display_languages = ['all'];

        $newForm->settings = [];
        foreach ($this->settings['options'] as $category => $options) {
            foreach ($options as $name => $oneOption) {
                $newForm->settings[$category][$name] = $oneOption['default'];
            }
        }
        foreach ($this->settings['styles'] as $category => $options) {
            foreach ($options as $name => $oneOption) {
                $newForm->settings[$category][$name] = $oneOption['default'];
            }
        }

        if ($type === self::SUB_FORM_TYPE_HEADER) {
            $newForm->settings['style']['position'] = 'button-left';
            $newForm->settings['style']['size'] = ['height' => '', 'width' => ''];
        } elseif ($type === self::SUB_FORM_TYPE_FOOTER) {
            $newForm->settings['style']['position'] = 'button-left';
            $newForm->settings['style']['size'] = ['width' => '100%', 'height' => '50'];
        }

        return $newForm;
    }

    public function getFormWithMissingParams($formArray): \stdClass
    {
        if (!is_array($formArray)) {
            $formArray = get_object_vars($formArray);
        }

        $form = new \stdClass();
        $formEmpty = $this->initEmptyForm($formArray['type']);

        if (!empty($formArray['id'])) {
            $form->id = $formArray['id'];
        }

        foreach ($formEmpty as $key => $value) {
            if ($key === 'settings') {
                continue;
            }

            if (isset($formArray[$key])) {
                $form->$key = $formArray[$key];
            } else {
                $form->$key = $value;
            }
        }

        $form->settings = $formEmpty->settings;
        foreach ($formArray['settings'] as $category => $options) {
            foreach ($options as $optionName => $value) {
                $form->settings[$category][$optionName] = $value;
            }
        }

        return $form;
    }

    public function prepareMenuHtml($form, $type): array
    {
        $sections = $this->getSectionTranslations();
        $htmlMenu = [];
        foreach ($this->settings[$type] as $category => $options) {
            $categoryOptions = [];
            foreach ($options as $key => $option) {
                if (!empty($option['allowed_types']) && !in_array($form->type, $option['allowed_types'])) {
                    continue;
                }

                $id = 'form_'.$category.'_'.$key;
                $name = 'form[settings]['.$category.']['.$key.']';
                $vModel = 'form.settings.'.$category.'.'.$key;
                $value = $form->settings[$category][$key] ?? $option['default'];

                $label = '<label class="cell" for="'.$id.'">'.acym_translation($option['label']);
                if (!empty($option['description'])) {
                    $label .= acym_info($option['description']);
                }
                $label .= '</label>';

                ob_start();
                include acym_getPartial('fields', $option['type']);
                $categoryOptions[$key] = $label.ob_get_clean();
            }

            if (!empty($categoryOptions)) {
                $htmlMenu[] = [
                    'title' => acym_translation($sections[$category]),
                    'render' => $categoryOptions,
                ];
            }
        }

        return $htmlMenu;
    }

    public function renderForm($form, $edition = false, $isShortcode = false)
    {
        if (!empty($form->display_languages) && !in_array('all', $form->display_languages) && !$edition) {
            if (!in_array(acym_getLanguageTag(), $form->display_languages)) {
                return '';
            }
        }
        $loadJsInModule = $isShortcode;
        if ($isShortcode && isset($form->settings['miscellaneous']['js_loading']) && $form->settings['miscellaneous']['js_loading'] === 'head') {
            $loadJsInModule = false;
        }
        acym_initModule(null, ['loadJsInModule' => $loadJsInModule]);
        $fieldClass = new FieldClass();
        $listClass = new ListClass();

        $form = $this->getFormWithMissingParams($form);

        $form->settings['fields']['displayed'][] = 2;
        $form->settings['fields']['displayed'] = $fieldClass->getFieldsByID($form->settings['fields']['displayed']);
        foreach ($form->settings['fields']['displayed'] as $key => $field) {
            $field->option = json_decode($field->option);
            $fieldDB = empty($field->option->fieldDB) ? '' : json_decode($field->option->fieldDB);
            $field->value = empty($field->value) ? '' : json_decode($field->value);
            $valuesArray = [];

            if (!empty($field->value)) {
                foreach ($field->value as $value) {
                    $valueTmp = new \stdClass();
                    $valueTmp->text = $value->title;
                    $valueTmp->value = $value->value;
                    if ($value->disabled == 'y') {
                        $valueTmp->disable = true;
                    }
                    $valuesArray[$value->value] = $valueTmp;
                }
            }

            if (!empty($fieldDB) && !empty($fieldDB->value)) {
                $fromDB = $fieldClass->getValueFromDB($fieldDB);
                foreach ($fromDB as $value) {
                    $valuesArray[$value->value] = $value->title;
                }
            }

            $form->settings['fields']['displayed'][$key]->valuesArray = $valuesArray;
        }
        $form->fieldClass = $fieldClass;
        $form->lists = $listClass->getAllForSelect(false, 0, true, true);
        $form->form_tag_name = 'formAcym'.$form->id;
        $form->form_tag_action = htmlspecialchars_decode(ACYM_CMS == 'wordpress' ? acym_frontendLink('frontusers') : acym_completeLink('frontusers', true, true));
        $form->formClass = $this;

        $formFieldRender = acym_getPartial('forms', $form->type);
        if (!file_exists($formFieldRender)) return '';

        acym_initModule(null, ['loadJsInModule' => $loadJsInModule]);

        ob_start();
        include $formFieldRender;

        return ob_get_clean();
    }
}
