<?php

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymParameter;

class acym_subscriptionform_widget extends WP_Widget
{
    public function __construct()
    {
        require_once rtrim(dirname(dirname(__DIR__)), DS).DS.'back'.DS.'helpers'.DS.'helper.php';

        parent::__construct(
            'acym_subscriptionform_widget',
            acym_translationSprintf('ACYM_MENU', acym_translation('ACYM_MENU_FORM')),
            ['description' => acym_translation('ACYM_MENU_FORM_DESC')]
        );
    }

    // Configuration
    public function form($instance)
    {
        require_once rtrim(dirname(dirname(__DIR__)), DS).DS.'back'.DS.'helpers'.DS.'helper.php';

        wp_enqueue_style('select2lib', ACYM_CSS.'libraries/select2-original.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'select2-original.min.css'));
        wp_enqueue_script('select2lib', ACYM_JS.'libraries/select2-full.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'select2-full.min.js'), ['jquery']);
        wp_enqueue_script('acym_widget_article', ACYM_JS.'widget.min.js', ['select2lib']);
        acym_addStyle(false, ACYM_CSS.'widget.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'widget.min.css'));

        $listClass = new ListClass();
        $fieldClass = new FieldClass();
        $allFields = $fieldClass->getAllfields();
        $fields = [];
        foreach ($allFields as $field) {
            if ($field->id == 2 || $field->active === '0') continue;
            $fields[$field->id] = acym_translation($field->name);
        }

        $lists = $listClass->getAllWithoutManagement();
        foreach ($lists as $i => $oneList) {
            if ($oneList->active == 0) {
                unset($lists[$i]);
            }
        }

        $listIds = array_keys($lists);
        $params = [
            'title' => 'Receive our newsletters',
            'mode' => 'tableless',
            'hiddenlists' => array_shift($listIds),
            'displists' => '',
            'listschecked' => '',
            'listposition' => 'before',
            'fields' => '1',
            'textmode' => '1',
            'subtext' => '',
            'subtextlogged' => '',
            'termscontent' => '0',
            'privacypolicy' => '0',
            'articlepopup' => '1',

            'unsub' => '0',
            'unsubtext' => '',
            'unsubredirect' => '',
            'successmode' => 'replace',
            'confirmation_message' => '',
            'redirect' => '',
            'introtext' => '',
            'posttext' => '',
            'userinfo' => '1',
            'formclass' => '',
            'alignment' => 'none',
            'source' => 'widget __i__',
            'includejs' => 'header',
        ];
        foreach ($params as $oneParam => &$value) {
            if (!empty($instance)) {
                if (isset($instance[$oneParam])) {
                    $value = $instance[$oneParam];
                } else {
                    $value = '';
                }
            }

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $value = esc_attr($value);
        }

        if (!isset($instance['hiddenlists']) && !empty($params['displists'])) {
            $params['hiddenlists'] = '';
        }

        echo '<div class="acyblock widget" id="mainopt_acywidget">
                <div class="widget-top">
                    <div class="widget-title-action">
                        <button type="button" class="widget-action hide-if-no-js" aria-expanded="false">
                            <span class="toggle-indicator" aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="widget-title"><h3>'.acym_translation('ACYM_MAIN_OPTIONS').'</h3></div>
                </div>
                <div class="widget-inside">';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('title').'">'.acym_translation('ACYM_TITLE').'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" value="'.$params['title'].'" /></p>';

        $options = [];
        $options[] = acym_selectOption('inline', 'ACYM_MODE_HORIZONTAL');
        $options[] = acym_selectOption('vertical', 'ACYM_MODE_VERTICAL');
        $options[] = acym_selectOption('tableless', 'ACYM_MODE_TABLELESS');
        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_DISPLAY_MODE_DESC').'">'.acym_translation('ACYM_DISPLAY_MODE').'</label>';
        echo acym_Select($options, $this->get_field_name('mode'), $params['mode'], 'class="acym_simple_select2"', 'value', 'text', $this->get_field_id('mode')).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_AUTO_SUBSCRIBE_TO_DESC').'">'.acym_translation('ACYM_AUTO_SUBSCRIBE_TO').'</label>';
        echo acym_selectMultiple(
            $lists,
            $this->get_field_name('hiddenlists'),
            explode(',', $params['hiddenlists']),
            ['class' => 'acym_simple_select2', 'id' => $this->get_field_id('hiddenlists')],
            'id',
            'name'
        );

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_DISPLAYED_LISTS_DESC').'">'.acym_translation('ACYM_DISPLAYED_LISTS').'</label>';
        echo acym_selectMultiple(
            $lists,
            $this->get_field_name('displists'),
            explode(',', $params['displists']),
            ['class' => 'acym_simple_select2', 'id' => $this->get_field_id('displists')],
            'id',
            'name'
        );

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_LISTS_CHECKED_DEFAULT_DESC').'">'.acym_translation('ACYM_LISTS_CHECKED_DEFAULT').'</label>';
        echo acym_selectMultiple(
            $lists,
            $this->get_field_name('listschecked'),
            explode(',', $params['listschecked']),
            ['class' => 'acym_simple_select2', 'id' => $this->get_field_id('listschecked')],
            'id',
            'name'
        );

        $options = [];
        $options[] = acym_selectOption('before', 'ACYM_BEFORE_FIELDS');
        $options[] = acym_selectOption('after', 'ACYM_AFTER_FIELDS');
        echo '<p><label class="acyWPconfig">'.acym_translation('ACYM_LIST_POSITION').'</label>';
        echo acym_select(
                $options,
                $this->get_field_name('listposition'),
                $params['listposition'],
                'class="acym_simple_select2"',
                'value',
                'text',
                $this->get_field_id('listposition')
            ).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_FIELDS_TO_DISPLAY_DESC').'">'.acym_translation('ACYM_FIELDS_TO_DISPLAY').'</label>';
        echo acym_selectMultiple(
            $fields,
            $this->get_field_name('fields'),
            explode(',', $params['fields']),
            ['class' => 'acym_simple_select2', 'id' => $this->get_field_id('fields')]
        );

        $options = [];
        $options[] = acym_selectOption('1', 'ACYM_TEXT_INSIDE');
        $options[] = acym_selectOption('0', 'ACYM_TEXT_OUTSIDE');
        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_TEXT_MODE_DESC').'">'.acym_translation('ACYM_TEXT_MODE').'</label>';
        echo acym_select($options, $this->get_field_name('textmode'), $params['textmode'], 'class="acym_simple_select2"', 'value', 'text', $this->get_field_id('textmode')).'</p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('subtext').'" title="'.acym_translation('ACYM_SUBSCRIBE_TEXT_DESC').'">'.acym_translation(
                'ACYM_SUBSCRIBE_TEXT'
            ).'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('subtext').'" name="'.$this->get_field_name('subtext').'" value="'.$params['subtext'].'" /></p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('subtextlogged').'" title="'.acym_translation('ACYM_SUBSCRIBE_TEXT_LOGGED_IN_DESC').'">'.acym_translation(
                'ACYM_SUBSCRIBE_TEXT_LOGGED_IN'
            ).'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('subtextlogged').'" name="'.$this->get_field_name(
                'subtextlogged'
            ).'" value="'.$params['subtextlogged'].'" /></p>';

        $options = [];
        if (!empty($params['termscontent'])) {
            $options[] = acym_selectOption($params['termscontent'], get_the_title($params['termscontent']));
        }
        echo '<p><label class="acyWPconfig">'.acym_translation('ACYM_TERMS_CONDITIONS').'</label>';
        echo acym_select(
                $options,
                $this->get_field_name('termscontent'),
                $params['termscontent'],
                'class="acym_post_select2" title="'.acym_translation('ACYM_PRIVACY_POLICY', true).'"',
                'value',
                'text',
                $this->get_field_id('termscontent')
            ).'</p>';

        $options = [];
        if (!empty($params['privacypolicy'])) {
            $options[] = acym_selectOption($params['privacypolicy'], get_the_title($params['privacypolicy']));
        }
        echo '<p><label class="acyWPconfig">'.acym_translation('ACYM_PRIVACY_POLICY').'</label>';
        echo acym_select(
                $options,
                $this->get_field_name('privacypolicy'),
                $params['privacypolicy'],
                'class="acym_post_select2" title="'.acym_translation('ACYM_PRIVACY_POLICY', true).'"',
                'value',
                'text',
                $this->get_field_id('privacypolicy')
            ).'</p>';

        //echo '<p><label class="acyWPconfig">'.acym_translation('ACYM_DISPLAY_ARTICLE_POPUP').'</label>';
        //echo acym_boolean($this->get_field_name('articlepopup'), $params['articlepopup'], $this->get_field_id('articlepopup'), array()).'</p>';

        echo '</div>
            </div>
            <div class="acyblock widget" id="advopt_acywidget">
                <div class="widget-top">
                    <div class="widget-title-action">
                        <button type="button" class="widget-action hide-if-no-js" aria-expanded="false">
                            <span class="toggle-indicator" aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="widget-title"><h3>'.acym_translation('ACYM_ADVANCED_OPTIONS').'</h3></div>
                </div>
                <div class="widget-inside">';

        echo '<p><label class="acyWPconfig">'.acym_translation('ACYM_DISPLAY_UNSUB_BUTTON').'</label>';
        $onclick = "var disp = 'none';";
        $onclick .= "if(this.value == 1){disp = 'block';}";
        $onclick .= "var elements = document.getElementsByClassName('".$this->get_field_id('unsubtextrow')."');";
        $onclick .= "for(var i = 0 ; i < elements.length ; i++){elements[i].style.display = disp;}";
        echo acym_boolean($this->get_field_name('unsub'), $params['unsub'], $this->get_field_id('unsub'), ['onclick' => $onclick]).'</p>';

        echo '<p class="'.$this->get_field_id('unsubtextrow').'" '.($params['unsub'] == '0' ? 'style="display:none;"' : '').'>
        	<label class="acyWPconfig" for="'.$this->get_field_id('unsubtext').'" title="'.acym_translation('ACYM_UNSUBSCRIBE_TEXT_DESC').'">'.acym_translation(
                'ACYM_UNSUBSCRIBE_TEXT'
            ).'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('unsubtext').'" name="'.$this->get_field_name('unsubtext').'" value="'.$params['unsubtext'].'" /></p>';

        echo '<p class="'.$this->get_field_id('unsubtextrow').'" '.($params['unsub'] == '0' ? 'style="display:none;"' : '').'>
        	<label class="acyWPconfig" for="'.$this->get_field_id('unsubredirect').'" title="'.acym_translation('ACYM_REDIRECT_LINK_UNSUB_DESC').'">'.acym_translation(
                'ACYM_REDIRECT_LINK_UNSUB'
            ).'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('unsubredirect').'" name="'.$this->get_field_name(
                'unsubredirect'
            ).'" value="'.$params['unsubredirect'].'" /></p>';

        $optionsSuccess = [];
        $optionsSuccess[] = acym_selectOption('replace', 'ACYM_SUCCESS_REPLACE');
        $optionsSuccess[] = acym_selectOption('replacetemp', 'ACYM_SUCCESS_REPLACE_TEMP');
        $optionsSuccess[] = acym_selectOption('toptemp', 'ACYM_SUCCESS_TOP_TEMP');
        $optionsSuccess[] = acym_selectOption('standard', 'ACYM_SUCCESS_STANDARD');
        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_SUCCESS_MODE_DESC').'">'.acym_translation('ACYM_SUCCESS_MODE').'</label>';
        echo acym_select(
                $optionsSuccess,
                $this->get_field_name('successmode'),
                $params['successmode'],
                'class="acym_simple_select2"',
                'value',
                'text',
                $this->get_field_id('successmode')
            ).'</p>';

        echo '<p>
				<label 
					class="acyWPconfig" 
					for="'.$this->get_field_id('confirmation_message').'" 
					title="'.acym_translation('ACYM_CONFIRMATION_MESSAGE_DESC').'">'.acym_translation('ACYM_CONFIRMATION_MESSAGE').'</label>
				<input 
					type="text" class="widefat" 
					id="'.$this->get_field_id('confirmation_message').'" 
					name="'.$this->get_field_name('confirmation_message').'" 
					value="'.acym_escape($params['confirmation_message']).'" />
			</p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('redirect').'" title="'.acym_translation('ACYM_REDIRECT_LINK_DESC').'">'.acym_translation(
                'ACYM_REDIRECT_LINK'
            ).'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('redirect').'" name="'.$this->get_field_name('redirect').'" value="'.$params['redirect'].'" /></p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('introtext').'" title="'.acym_translation('ACYM_INTRO_TEXT_DESC').'">'.acym_translation('ACYM_INTRO_TEXT').'</label>
			<textarea class="widefat" id="'.$this->get_field_id('introtext').'" name="'.$this->get_field_name('introtext').'" >'.$params['introtext'].'</textarea></p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('posttext').'" title="'.acym_translation('ACYM_POST_TEXT_DESC').'">'.acym_translation('ACYM_POST_TEXT').'</label>
			<textarea class="widefat" id="'.$this->get_field_id('posttext').'" name="'.$this->get_field_name('posttext').'" >'.$params['posttext'].'</textarea></p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_FORM_AUTOFILL_ID_DESC').'">'.acym_translation('ACYM_FORM_AUTOFILL_ID').'</label>';
        echo acym_boolean($this->get_field_name('userinfo'), $params['userinfo'], $this->get_field_id('userinfo')).'</p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('formclass').'" title="'.acym_translation('ACYM_FORM_CLASS_DESC').'">'.acym_translation('ACYM_FORM_CLASS').'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('formclass').'" name="'.$this->get_field_name('formclass').'" value="'.$params['formclass'].'" /></p>';

        $options = [];
        $options[] = acym_selectOption('header', 'ACYM_IN_HEADER');
        $options[] = acym_selectOption('module', 'ACYM_ON_THE_MODULE');
        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_MODULE_JS_DESC').'">'.acym_translation('ACYM_MODULE_JS').'</label>';
        echo acym_Select(
                $options,
                $this->get_field_name('includejs'),
                $params['includejs'],
                'class="acym_simple_select2"',
                'value',
                'text',
                $this->get_field_id('includejs')
            ).'</p>';

        $options = [];
        $options[] = acym_selectOption('none', 'ACYM_DEFAULT');
        $options[] = acym_selectOption('left', 'ACYM_LEFT');
        $options[] = acym_selectOption('center', 'ACYM_CENTER');
        $options[] = acym_selectOption('right', 'ACYM_RIGHT');
        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_ALIGNMENT_DESC').'">'.acym_translation('ACYM_ALIGNMENT').'</label>';
        echo acym_select(
                $options,
                $this->get_field_name('alignment'),
                $params['alignment'],
                'class="acym_simple_select2"',
                'value',
                'text',
                $this->get_field_id('alignment')
            ).'</p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('source').'" title="'.acym_translation('ACYM_SOURCE_DESC').'">'.acym_translation('ACYM_SOURCE').'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('source').'" name="'.$this->get_field_name('source').'" value="'.$params['source'].'" /></p>';

        echo '</div></div>';
    }

    // Widget's output
    public function widget($args, $instance)
    {
        require_once rtrim(dirname(dirname(__DIR__)), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';

        echo $args['before_widget'];

        $params = new acymParameter($instance);
        acym_initModule($params);

        if(!isset($instance['title'])) $instance['title'] = '';
        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title)) {
            echo $args['before_title'].$title.$args['after_title'];
        }

        $identifiedUser = null;
        $currentUserEmail = acym_currentUserEmail();
        if ($params->get('userinfo', '1') == '1' && !empty($currentUserEmail)) {
            $userClass = new UserClass();
            $identifiedUser = $userClass->getOneByEmail($currentUserEmail);
        }

        $visibleLists = $params->get('displists', []);
        $hiddenLists = $params->get('hiddenlists', []);
        $allfields = $params->get('fields', []);
        if (!in_array('2', $allfields)) {
            $allfields[] = 2;
        }
        acym_arrayToInteger($visibleLists);
        acym_arrayToInteger($hiddenLists);
        acym_arrayToInteger($allfields);

        $listClass = new ListClass();
        $fieldClass = new FieldClass();

        $allLists = $listClass->getAllWithoutManagement();
        $visibleLists = array_intersect($visibleLists, array_keys($allLists));
        $hiddenLists = array_intersect($hiddenLists, array_keys($allLists));

        $allfields = $fieldClass->getFieldsByID($allfields);
        $fields = [];
        foreach ($allfields as $field) {
            if ($field->active === '0') continue;
            $fields[$field->id] = $field;
        }

        if (empty($visibleLists) && empty($hiddenLists)) {
            $hiddenLists = array_keys($allLists);
        }

        // Make sure we don't display a list that's in "automatically subscribe to"
        if (!empty($visibleLists) && !empty($hiddenLists)) {
            $visibleLists = array_diff($visibleLists, $hiddenLists);
        }

        if (empty($identifiedUser->id)) {
            //Check lists based on the option
            $checkedLists = $params->get('listschecked', []);
            if (!is_array($checkedLists)) {
                if (strtolower($checkedLists) == 'all') {
                    $checkedLists = $visibleLists;
                } elseif (strpos($checkedLists, ',') || is_numeric($checkedLists)) {
                    $checkedLists = explode(',', $checkedLists);
                } else {
                    $checkedLists = [];
                }
            }
        } else {
            $checkedLists = [];
            $userLists = $userClass->getUserSubscriptionById($identifiedUser->id);

            $countSub = 0;
            $countUnsub = 0;
            $formLists = array_merge($visibleLists, $hiddenLists);
            foreach ($formLists as $idOneList) {
                if (empty($userLists[$idOneList]) || $userLists[$idOneList]->status == 0) {
                    $countSub++;
                } else {
                    $countUnsub++;
                    $checkedLists[] = $idOneList;
                }
            }
        }
        acym_arrayToInteger($checkedLists);


        $config = acym_config();

        // Texts
        $subscribeText = $params->get('subtext', 'ACYM_SUBSCRIBE');
        if (!empty($identifiedUser->id)) $subscribeText = $params->get('subtextlogged', 'ACYM_SUBSCRIBE');
        $unsubscribeText = $params->get('unsubtext', 'ACYM_UNSUBSCRIBE');

        // Formatting
        $listPosition = $params->get('listposition', 'before');
        $displayOutside = $params->get('textmode') == '0';

        // Display success message
        $successMode = $params->get('successmode', 'replace');

        // Redirections
        $redirectURL = $params->get('redirect', '');
        $unsubRedirectURL = $params->get('unsubredirect', '');
        $ajax = empty($redirectURL) && empty($unsubRedirectURL) && $successMode != 'standard' ? '1' : '0';

        // Customization
        $formClass = $params->get('formclass', '');
        $alignment = $params->get('alignment', 'none');
        $style = $alignment == 'none' ? '' : 'style="text-align: '.$alignment.'"';

        // Articles
        //TODO: Find a way to easily have a direct link to a page with only one WP post displayed (nothing else, no menu etc)
        $displayInAPopup = 0; // $params->get('articlepopup', 1);
        $termsURL = acym_getArticleURL($params->get('termscontent', 0), $displayInAPopup, 'ACYM_TERMS_CONDITIONS');
        $privacyURL = acym_getArticleURL($params->get('privacypolicy', 0), $displayInAPopup, 'ACYM_PRIVACY_POLICY');

        if (empty($termsURL) && empty($privacyURL)) {
            $termslink = '';
        } elseif (empty($privacyURL)) {
            $termslink = acym_translationSprintf('ACYM_I_AGREE_TERMS', $termsURL);
        } elseif (empty($termsURL)) {
            $termslink = acym_translationSprintf('ACYM_I_AGREE_PRIVACY', $privacyURL);
        } else {
            $termslink = acym_translationSprintf('ACYM_I_AGREE_BOTH', $termsURL, $privacyURL);
        }

        $formName = acym_getModuleFormName();
        $formAction = htmlspecialchars_decode(acym_frontendLink('frontusers'));

        $js = "window.addEventListener('DOMContentLoaded', (event) => {";
        $js .= "\n"."acymModule['excludeValues".$formName."'] = [];";
        $fieldsToDisplay = [];
        foreach ($fields as $field) {
            $fieldsToDisplay[$field->id] = $field->name;
            $js .= "\n"."acymModule['excludeValues".$formName."']['".$field->id."'] = '".acym_translation($field->name, true)."';";
        }
        $js .= "  });";
        // Exclude default values from fields, if the user didn't fill them in
        echo '<script type="text/javascript">
                <!--
                '.$js.'
                //-->
                </script>';

        ?>
		<div class="acym_module <?php echo acym_escape($formClass); ?>" id="acym_module_<?php echo $formName; ?>">
			<div class="acym_fulldiv" id="acym_fulldiv_<?php echo $formName; ?>" <?php echo $style; ?>>
				<form enctype="multipart/form-data"
					  id="<?php echo acym_escape($formName); ?>"
					  name="<?php echo acym_escape($formName); ?>"
					  method="POST"
					  action="<?php echo acym_escape($formAction); ?>"
					  onsubmit="return submitAcymForm('subscribe','<?php echo $formName; ?>', 'acymSubmitSubForm')">
					<div class="acym_module_form">
                        <?php
                        $introText = $params->get('introtext', '');
                        if (!empty($introText)) {
                            echo '<div class="acym_introtext">'.$introText.'</div>';
                        }
                        if ($params->get('mode', 'tableless') == 'tableless') {
                            include __DIR__.DS.'tmpl'.DS.'tableless.php';
                        } else {
                            $displayInline = $params->get('mode', 'tableless') != 'vertical';
                            include __DIR__.DS.'tmpl'.DS.'default.php';
                        }
                        ?>
					</div>

					<input type="hidden" name="ctrl" value="frontusers" />
					<input type="hidden" name="task" value="notask" />
					<input type="hidden" name="option" value="<?php echo acym_escape(ACYM_COMPONENT); ?>" />

                    <?php
                    if (!empty($redirectURL)) echo '<input type="hidden" name="redirect" value="'.acym_escape($redirectURL).'"/>';
                    if (!empty($unsubRedirectURL)) echo '<input type="hidden" name="redirectunsub" value="'.acym_escape($unsubRedirectURL).'"/>';
                    ?>
					<input type="hidden" name="ajax" value="<?php echo acym_escape($ajax); ?>" />
					<input type="hidden" name="successmode" value="<?php echo acym_escape($successMode); ?>" />
					<input type="hidden" name="acy_source" value="<?php echo acym_escape($params->get('source', '')); ?>" />
					<input type="hidden" name="hiddenlists" value="<?php echo implode(',', $hiddenLists); ?>" />
					<input type="hidden" name="acyformname" value="<?php echo acym_escape($formName); ?>" />
					<input type="hidden" name="acysubmode" value="widget_acym" />
					<input type="hidden" name="confirmation_message" value="<?php echo acym_escape($params->get('confirmation_message', '')); ?>" />

                    <?php
                    $postText = $params->get('posttext', '');
                    if (!empty($postText)) {
                        echo '<div class="acym_posttext">'.$postText.'</div>';
                    }
                    ?>
				</form>
			</div>
		</div>
        <?php
        echo $args['after_widget'];
    }
}
