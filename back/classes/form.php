<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\FormPositionHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class FormClass extends acymClass
{
    var $table = 'form';
    var $pkey = 'id';
    var $positionHelper;
    public $emptyModel;

    const SUB_FORM_TYPE_SHORTCODE = 'shortcode';
    const SUB_FORM_TYPE_POPUP = 'popup';
    const SUB_FORM_TYPE_HEADER = 'header';
    const SUB_FORM_TYPE_FOOTER = 'footer';

    public function __construct()
    {
        parent::__construct();
        $this->positionHelper = new FormPositionHelper();
        $this->emptyModel = $this->initEmptyForm(self::SUB_FORM_TYPE_FOOTER);
    }

    public function getConstPopup()
    {
        return self::SUB_FORM_TYPE_POPUP;
    }

    public function getConstShortcode()
    {
        return self::SUB_FORM_TYPE_SHORTCODE;
    }

    public function getConstFooter()
    {
        return self::SUB_FORM_TYPE_FOOTER;
    }

    public function getConstHeader()
    {
        return self::SUB_FORM_TYPE_HEADER;
    }

    public function getMatchingElements($settings = [])
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

    /**
     * @param string $typeConst
     *
     * @return array
     */
    public function getTranslatedTypes()
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

        if (empty($form)) return $form;

        foreach ($form as $key => $value) {
            $value = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) $form->$key = $value;
        }

        return $form;
    }

    public function getAllFormsToDisplay()
    {
        $forms = acym_loadObjectList('SELECT * FROM #__acym_form WHERE active = 1 AND type != '.acym_escapeDB(self::SUB_FORM_TYPE_SHORTCODE));
        foreach ($forms as $key => $form) {
            foreach ($form as $formKey => $value) {
                $value = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) $form->$formKey = $value;
            }
            $forms[$key] = $form;
        }

        return $forms;
    }

    public function initEmptyForm($type)
    {
        $newForm = new \stdClass();
        $newForm->name = '';
        $newForm->creation_date = acym_date('now', 'Y-m-d H:i:s');
        $newForm->active = 1;
        $newForm->type = $type;
        $newForm->lists_options = [
            'automatic_subscribe' => [],
            'displayed' => [],
            'checked' => [],
            'display_position' => 'after',
        ];
        $newForm->fields_options = [
            'displayed' => [],
            'display_mode' => 'inside',
        ];
        $newForm->termspolicy_options = [
            'termscond' => 0,
            'privacy' => 0,
        ];
        if (in_array($type, [self::SUB_FORM_TYPE_FOOTER, self::SUB_FORM_TYPE_HEADER, self::SUB_FORM_TYPE_POPUP])) {
            $newForm->cookie = [
                'cookie_expiration' => 1,
            ];
        }
        if ($type == self::SUB_FORM_TYPE_POPUP) {
            $newForm->style_options = [
                'position' => 'image-top',
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'padding' => ['width' => '20', 'height' => '20'],
            ];
        } elseif ($type == self::SUB_FORM_TYPE_HEADER) {
            $newForm->style_options = [
                'position' => 'button-left',
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'size' => ['height' => '', 'width' => ''],
            ];
        } elseif ($type == self::SUB_FORM_TYPE_FOOTER) {
            $newForm->style_options = [
                'position' => 'button-left',
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'size' => ['width' => '100%', 'height' => '50'],
            ];
        } else {
            $newForm->style_options = [
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'size' => ['width' => '400', 'height' => '300'],
            ];
        }
        $newForm->button_options = [
            'text' => acym_translation('ACYM_SUBSCRIBE'),
            'background_color' => '#000000',
            'text_color' => '#ffffff',
            'border_color' => '#000000',
            'border_type' => 'solid',
            'border_size' => '0',
            'border_radius' => '0',
            'size' => ['height' => 10, 'width' => 20],
        ];
        if ($type == self::SUB_FORM_TYPE_POPUP) {
            $newForm->image_options = [
                'url' => '',
                'size' => ['width' => 100, 'height' => 100],
            ];
        }
        $newForm->delay = 0;
        $newForm->pages = [];
        $newForm->redirection_options = [
            'after_subscription' => '',
            'confirmation_message' => '',
        ];
        $newForm->id = 0;

        return $newForm;
    }

    public function prepareMenuHtmlSettings($form)
    {
        $htmlMenu = [];
        foreach ($form as $key => $value) {
            if (!$this->shouldHaveOption($form->type, $key)) continue;
            $functionName = 'prepareMenuHtmlSettings_'.$key;
            if (!empty($this->emptyModel->$key) && is_array($this->emptyModel->$key)) {
                foreach ($this->emptyModel->$key as $oneOption => $defaultValue) {
                    if (!isset($value[$oneOption])) $value[$oneOption] = $defaultValue;
                }
            }
            if (method_exists($this, $functionName)) $htmlMenu[$key] = $this->$functionName($key, $value);
        }

        return $htmlMenu;
    }

    private function shouldHaveOption($formType, $option)
    {
        if ($formType == self::SUB_FORM_TYPE_SHORTCODE && $option == 'cookie') return false;

        return true;
    }

    public function prepareMenuHtmlStyle($form)
    {
        $htmlMenu = [];
        foreach ($form as $key => $value) {
            $functionName = 'prepareMenuHtmlStyle_'.$key;
            if (method_exists($this, $functionName) && !empty($value)) $htmlMenu[$key] = $this->$functionName($key, $value, $form->type);
        }

        return $htmlMenu;
    }

    private function prepareMenuHtmlStyle_image_options($optionName, $options, $type)
    {
        $return = [
            'title' => acym_translation('ACYM_IMAGE'),
        ];

        foreach ($options as $key => $value) {
            $name = 'form['.$optionName.']['.$key.']';
            $vModel = 'form.'.$optionName.'.'.$key;
            if ($key == 'url') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_CHOOSE_IMAGE').'</label>';
                $return['render'][$key] .= '<acym-media :value="'.$vModel.'" :text="imageText" v-on:change="'.$vModel.' = $event">';
            } elseif ($key == 'size') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_SIZE').'</label>';
                $return['render'][$key] .= '<input type="number" class="cell medium-3 margin-right-0" v-model="'.$vModel.'.height'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span>';
                $return['render'][$key] .= '<span>x</span>';
                $return['render'][$key] .= '<input type="number" class="cell medium-3 margin-right-0" v-model="'.$vModel.'.width'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span>';
            }
        }

        return $return;
    }

    private function prepareMenuHtmlStyle_button_options($optionName, $options, $type)
    {
        $return = [
            'title' => acym_translation('ACYM_BUTTON'),
        ];
        $return['render'] = [];
        foreach ($options as $key => $value) {
            $name = 'form['.$optionName.']['.$key.']';
            $vModel = 'form.'.$optionName.'.'.$key;
            if ($key == 'position') {
                $functionName = 'renderPosition_'.$type;
                if (!method_exists($this, $functionName)) continue;
                $return['render'][$key] = $this->$functionName($vModel);
            } elseif ($key == 'background_color') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_BACKGROUND_COLOR').'</label>';
                $return['render'][$key] .= '<spectrum :name="\''.$name.'\'" v-model="'.$vModel.'" :value="\''.$value.'\'">';
            } elseif ($key == 'text_color') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_TEXT_COLOR').'</label>';
                $return['render'][$key] .= '<spectrum :name="\''.$name.'\'" v-model="'.$vModel.'" :value="\''.$value.'\'">';
            } elseif ($key == 'border_color') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_BORDER_COLOR').'</label>';
                $return['render'][$key] .= '<spectrum :name="\''.$name.'\'" v-model="'.$vModel.'" :value="\''.$value.'\'">';
            } elseif ($key == 'border_type') {
                $borderTypes = [
                    'solid' => acym_translation('ACYM_SOLID'),
                    'dotted' => acym_translation('ACYM_DOTTED'),
                    'dashed' => acym_translation('ACYM_DASHED'),
                    'double' => acym_translation('ACYM_DOUBLE'),
                    'groove' => acym_translation('ACYM_GROOVE'),
                    'ridge' => acym_translation('ACYM_RIDGE'),
                    'inset' => acym_translation('ACYM_INSET'),
                    'outset' => acym_translation('ACYM_OUTSET'),
                ];
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_BORDER_TYPE').'</label>';
                $return['render'][$key] .= '<div class="cell auto">
                                                <select2 :name="\''.$name.'\'" :value="\''.$value.'\'" :options="'.acym_escape(json_encode($borderTypes)).'" v-model="'.$vModel.'"></select2>
                                            </div>';
            } elseif ($key == 'border_size') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_BORDER_SIZE').'</label>';
                $return['render'][$key] .= '<input type="number" class="cell medium-3" name="'.$name.'" v-model="'.$vModel.'">';
            } elseif ($key == 'border_radius') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_RADIUS').'</label>';
                $return['render'][$key] .= '<input type="number" class="cell medium-3" name="'.$name.'" v-model="'.$vModel.'">';
            } elseif ($key == 'size') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_SIZE').'</label>';
                $return['render'][$key] .= '<input type="number" class="cell medium-3 margin-right-0" v-model="'.$vModel.'.height'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span>';
                $return['render'][$key] .= '<span>x</span>';
                $return['render'][$key] .= '<input type="number" class="cell medium-3 margin-right-0" v-model="'.$vModel.'.width'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span>';
            } elseif ($key == 'text') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_TEXT').'</label>';
                $return['render'][$key] .= '<input type="text" class="cell auto" v-model="'.$vModel.'" name="'.$name.'">';
            }
        }

        return $return;
    }

    private function prepareMenuHtmlStyle_style_options($optionName, $options, $type)
    {
        $typeTraduction = $this->getTranslatedTypes();
        $return = [
            'title' => $typeTraduction[$type],
        ];
        $return['render'] = [];
        foreach ($options as $key => $value) {
            $name = 'form['.$optionName.']['.$key.']';
            $vModel = 'form.'.$optionName.'.'.$key;
            if ($key == 'position') {
                $functionName = 'renderPosition_'.$type;
                if (!method_exists($this, $functionName)) continue;
                $return['render'][$key] = $this->$functionName($vModel);
            } elseif ($key == 'background_color') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_BACKGROUND_COLOR').'</label>';
                $return['render'][$key] .= '<spectrum :name="\''.$name.'\'" v-model="'.$vModel.'" :value="\''.$value.'\'">';
            } elseif ($key == 'text_color') {
                $return['render'][$key] = '<label class="cell medium-4">'.acym_translation('ACYM_TEXT_COLOR').'</label>';
                $return['render'][$key] .= '<spectrum :name="\''.$name.'\'" v-model="'.$vModel.'" :value="\''.$value.'\'">';
            } elseif ($key == 'size') {
                $functionName = 'renderSize_'.$type;
                if (!method_exists($this, $functionName)) continue;
                $return['render'][$key] = $this->$functionName($vModel);
            } elseif ($key == 'padding') {
                $functionName = 'renderPadding_'.$type;
                if (!method_exists($this, $functionName)) continue;
                $return['render'][$key] = $this->$functionName($vModel);
            }
        }

        return $return;
    }

    private function renderPosition_popup($vModel)
    {
        $html = '<label class="cell medium-4">'.acym_translation('ACYM_POSITION').'</label>';
        $html .= $this->positionHelper->displayPositionButtons(['image-top', 'image-bottom', 'image-right', 'image-left'], $vModel);

        return $html;
    }

    private function renderPosition_header($vModel)
    {
        $html = '<label class="cell medium-4">'.acym_translation('ACYM_POSITION').'</label>';
        $html .= $this->positionHelper->displayPositionButtons(['button-left', 'button-right'], $vModel);

        return $html;
    }

    private function renderPosition_footer($vModel)
    {
        $html = '<label class="cell medium-4">'.acym_translation('ACYM_POSITION').'</label>';
        $html .= $this->positionHelper->displayPositionButtons(['button-left', 'button-right'], $vModel);

        return $html;
    }

    private function renderSize_header($vModel)
    {
        $html = '<label class="cell medium-4">'.acym_translation('ACYM_SIZE').'</label>';
        $html .= '<input type="number" min="0" max="100" class="cell medium-3 margin-right-0" v-model="'.$vModel.'.width'.'" placeholder="100"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">%</span>';
        $html .= '<span>x</span>';
        $html .= '<input type="number" min="0" class="cell medium-3 margin-right-0" v-model="'.$vModel.'.height'.'" placeholder="50"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span>';

        return $html;
    }

    private function renderPadding_popup($vModel)
    {
        $html = '<label class="cell medium-4">'.acym_translation('ACYM_PADDING').'</label>';
        $html .= '<input type="number" class="cell medium-3" v-model="'.$vModel.'.width'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span><span>x</span><input type="number" class="cell medium-3" v-model="'.$vModel.'.height'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span>';

        return $html;
    }

    private function renderSize_shortcode($vModel)
    {
        $html = '<label class="cell medium-4">'.acym_translation('ACYM_SIZE').'</label>';
        $html .= '<input type="number" class="cell medium-3" v-model="'.$vModel.'.width'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span><span>x</span><input type="number" class="cell medium-3" v-model="'.$vModel.'.height'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span>';

        return $html;
    }

    private function renderSize_footer($vModel)
    {
        $html = '<label class="cell medium-4">'.acym_translation('ACYM_SIZE').'</label>';
        $html .= '<div class="cell grid-x auto acym_vcenter"><span class="cell shrink acym__forms__menu__options__style__size__default margin-right-1">100%</span><span class="cell medium-1">x</span><input type="number" class="cell medium-3" v-model="'.$vModel.'.height'.'"><span class="cell shrink acym__forms__menu__options__style__size__default margin-left-0">px</span></div>';

        return $html;
    }

    private function prepareMenuHtmlSettings_lists_options($optionName, $options)
    {
        $return = [
            'title' => acym_translation('ACYM_LISTS'),
        ];
        $return['render'] = [];
        $listClass = new ListClass();
        $lists = $listClass->getAllForSelect(false);
        foreach ($options as $key => $value) {
            $name = 'form['.$optionName.']['.$key.']';
            $vModel = 'form.'.$optionName.'.'.$key;
            if ($key == 'automatic_subscribe') {
                $return['render'][$key] = '<label class="cell grid-x acym_vcenter">'.acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info('ACYM_AUTO_SUBSCRIBE_TO_DESC').'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2multiple :name="\''.$name.'\'" :value="\''.acym_escape(json_encode($value)).'\'" :options="'.acym_escape(
                        json_encode($lists)
                    ).'" v-model="'.$vModel.'"></select2multiple>
                                            </div>';
            } elseif ($key == 'displayed') {
                $return['render'][$key] = '<label class="cell grid-x acym_vcenter">'.acym_translation('ACYM_DISPLAYED_LISTS').acym_info('ACYM_DISPLAYED_LISTS_DESC').'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2multiple :name="\''.$name.'\'" :value="\''.acym_escape(json_encode($value)).'\'" :options="'.acym_escape(
                        json_encode($lists)
                    ).'" v-model="'.$vModel.'"></select2multiple>
                                            </div>';
            } elseif ($key == 'checked') {
                $return['render'][$key] = '<label class="cell grid-x acym_vcenter">'.acym_translation('ACYM_LISTS_CHECKED_DEFAULT').acym_info(
                        'ACYM_LISTS_CHECKED_DEFAULT_DESC'
                    ).'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2multiple :name="\''.$name.'\'" :value="\''.acym_escape(json_encode($value)).'\'" :options="'.acym_escape(
                        json_encode($lists)
                    ).'" v-model="'.$vModel.'"></select2multiple>
                                            </div>';
            } elseif ($key == 'display_position') {
                $displayPositions = [
                    'after' => acym_translation('ACYM_AFTER_FIELDS'),
                    'before' => acym_translation('ACYM_BEFORE_FIELDS'),
                ];
                $return['render'][$key] = '<label class="cell">'.acym_translation('ACYM_DISPLAY_LISTS').'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2 :name="\''.$name.'\'" :value="\''.$value.'\'" :options="'.acym_escape(
                        json_encode($displayPositions)
                    ).'" v-model="'.$vModel.'"></select2>
                                            </div>';
            }
        }

        return $return;
    }

    private function prepareMenuHtmlSettings_fields_options($optionName, $options)
    {
        $return = [
            'title' => acym_translation('ACYM_FIELDS'),
        ];
        $return['render'] = [];
        $fieldClass = new FieldClass();
        $allFields = $fieldClass->getAll();
        $fields = [];
        foreach ($allFields as $field) {
            if ($field->id == 2 || $field->active === '0') continue;
            $fields[$field->id] = acym_translation($field->name);
        }
        foreach ($options as $key => $value) {
            $name = 'form['.$optionName.']['.$key.']';
            $vModel = 'form.'.$optionName.'.'.$key;
            if ($key == 'displayed') {
                $return['render'][$key] = '<label class="cell">'.acym_translation('ACYM_FIELDS_TO_DISPLAY').'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2multiple :name="\''.$name.'\'" :value="\''.acym_escape(json_encode($value)).'\'" :options="'.acym_escape(
                        json_encode($fields)
                    ).'" v-model="'.$vModel.'"></select2multiple>
                                            </div>';
            } elseif ($key == 'display_mode') {
                $displayModes = [
                    'inside' => acym_translation('ACYM_TEXT_INSIDE'),
                    'outside' => acym_translation('ACYM_TEXT_OUTSIDE'),
                ];
                $return['render'][$key] = '<label class="cell">'.acym_translation('ACYM_DISPLAY_FIELDS_LABEL').'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2 :name="\''.$name.'\'" :value="\''.$value.'\'" :options="'.acym_escape(json_encode($displayModes)).'" v-model="'.$vModel.'"></select2>
                                            </div>';
            }
        }

        return $return;
    }

    private function prepareMenuHtmlSettings_termspolicy_options($optionName, $options)
    {
        $return = [
            'title' => acym_translation('ACYM_ARTICLE'),
        ];
        $return['render'] = [];
        foreach ($options as $key => $value) {
            $name = 'form['.$optionName.']['.$key.']';
            $vModel = 'form.'.$optionName.'.'.$key;

            if ($key == 'termscond') {
                $return['render'][$key] = '<label class="cell">'.acym_translation('ACYM_TERMS_CONDITIONS').'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2ajax :name="\''.$name.'\'" :value="\''.$value.'\'" v-model="'.$vModel.'" :urlselected="\'&ctrl=forms&task=getArticlesById&article_id=\'" :ctrl="\'forms\'" :task="\'getArticles\'"></select2ajax>
                                            </div>';
            } elseif ($key == 'privacy') {
                $return['render'][$key] = '<label class="cell">'.acym_translation('ACYM_PRIVACY_POLICY').'</label>';
                $return['render'][$key] .= '<div class="cell">
                                                <select2ajax :name="\''.$name.'\'" :value="\''.$value.'\'" v-model="'.$vModel.'" :urlselected="\'&ctrl=forms&task=getArticlesById&article_id=\'" :ctrl="\'forms\'" :task="\'getArticles\'"></select2ajax>
                                            </div>';
            }
        }

        return $return;
    }


    private function prepareMenuHtmlSettings_cookie($optionName, $options)
    {
        if (empty($options)) return '';
        $return = [
            'title' => acym_translation('ACYM_COOKIE_SETTINGS'),
        ];
        $return['render'] = [];
        foreach ($options as $key => $value) {
            $name = 'form['.$optionName.']['.$key.']';
            $vModel = 'form.'.$optionName.'.'.$key;

            if ($key == 'cookie_expiration') {
                $return['render'][$key] = '<label class="cell">'.acym_translation('ACYM_COOKIE_EXPIRATION').'</label>';
                $return['render'][$key] .= '<div class="cell grid-x acym_vcenter">
                                                <input min="1" type="number" class="cell medium-3 margin-right-1" v-model="'.$vModel.'">
                                                <span class="cell shrink">'.acym_translation('ACYM_DAYS').'</span>
                                            </div>';
            }
        }

        return $return;
    }

    private function prepareMenuHtmlSettings_redirection_options($categoryName, $options)
    {
        $return = [
            'title' => acym_translation('ACYM_REDIRECTIONS'),
        ];

        foreach ($options as $key => $value) {
            $id = 'form_'.$categoryName.'_'.$key;
            $vModel = 'form.'.$categoryName.'.'.$key;

            $return['render'][$key] = '<label class="cell" for="'.$id.'">';
            if ($key === 'after_subscription') {
                $return['render'][$key] .= acym_translation('ACYM_AFTER_SUBSCRIPTION');
                $return['render'][$key] .= acym_info('ACYM_REDIRECT_LINK_DESC');
            } elseif ($key === 'confirmation_message') {
                $return['render'][$key] .= acym_translation('ACYM_CONFIRMATION_MESSAGE');
                $return['render'][$key] .= acym_info('ACYM_CONFIRMATION_MESSAGE_DESC');
            }
            $return['render'][$key] .= '</label>';
            $return['render'][$key] .= '<input type="text" class="cell" id="'.$id.'" v-model="'.$vModel.'">';
        }


        return $return;
    }

    public function renderForm($form, $edition = false)
    {
        acym_initModule();
        $fieldClass = new FieldClass();
        $listClass = new ListClass();
        $form->fields_options['displayed'][] = 2;
        $form->fields_options['displayed'] = $fieldClass->getFieldsByID($form->fields_options['displayed']);
        foreach ($form->fields_options['displayed'] as $key => $field) {
            $fieldDB = empty($field->option->fieldDB) ? '' : json_decode($field->option->fieldDB);
            $field->value = empty($field->value) ? '' : json_decode($field->value);
            $field->option = json_decode($field->option);
            $valuesArray = [];
            if (!empty($field->value)) {
                foreach ($field->value as $value) {
                    $valueTmp = new \stdClass();
                    $valueTmp->text = $value->title;
                    $valueTmp->value = $value->value;
                    if ($value->disabled == 'y') $valueTmp->disable = true;
                    $valuesArray[$value->value] = $valueTmp;
                }
            }
            if (!empty($fieldDB) && !empty($fieldDB->value)) {
                $fromDB = $fieldClass->getValueFromDB($fieldDB);
                foreach ($fromDB as $value) {
                    $valuesArray[$value->value] = $value->title;
                }
            }

            $form->fields_options['displayed'][$key]->valuesArray = $valuesArray;
        }
        $form->fieldClass = $fieldClass;
        $form->lists = $listClass->getAllForSelect(false, 0, true);

        $form->form_tag_name = 'formAcym'.$form->id;
        $form->form_tag_action = htmlspecialchars_decode(ACYM_CMS == 'wordpress' ? acym_frontendLink('frontusers') : acym_completeLink('frontusers', true, true));
        $form->formClass = $this;

        $formFieldRender = ACYM_PARTIAL.'forms'.DS.$form->type.'.php';

        if (!file_exists($formFieldRender)) return '';

        ob_start();
        include $formFieldRender;

        return ob_get_clean();
    }
}
