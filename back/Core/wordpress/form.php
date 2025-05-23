<?php

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Core\AcymParameter;
use AcyMailing\FrontControllers\FrontusersController;
use AcyMailing\FrontControllers\ArchiveController;

function acym_formToken()
{
    return '<input type="hidden" name="_wpnonce" value="'.wp_create_nonce('acymnonce').'">';
}

/**
 * Check token with all the possibilities
 */
function acym_checkToken()
{
    $token = acym_getVar('cmd', '_wpnonce');
    if (!wp_verify_nonce($token, 'acymnonce')) {
        die('Invalid Token');
    }
}

function acym_getFormToken()
{
    $token = acym_getVar('cmd', '_wpnonce', '');
    if (empty($token)) {
        $token = wp_create_nonce('acymnonce');
        acym_setVar('_wpnonce', $token);
    }

    return '_wpnonce='.$token;
}

function acym_noTemplate(): string
{
    return 'noheader=1';
}

function acym_isNoTemplate(): bool
{
    return acym_getVar('cmd', 'noheader') == '1';
}

function acym_setNoTemplate(bool $status = true)
{
    if ($status) {
        acym_setVar('noheader', '1');
    } else {
        unset($_REQUEST['noheader']);
    }
}


/**
 * @param bool   $token
 * @param string $task
 * @param string $currentStep
 * @param string $currentCtrl
 * @param bool   $addPage
 */
function acym_formOptions(bool $token = true, string $task = '', string $currentStep = '', string $currentCtrl = '', bool $addPage = true)
{
    if (!empty($currentStep)) {
        echo '<input type="hidden" name="step" value="'.acym_escape($currentStep).'"/>';
    }
    echo '<input type="hidden" name="nextstep" value=""/>';
    echo '<input type="hidden" name="task" value="'.acym_escape($task).'"/>';
    if ($addPage) {
        echo '<input type="hidden" name="page" value="'.acym_escape(acym_getVar('cmd', 'page', '')).'"/>';
    }
    echo '<input type="hidden" name="ctrl" value="'.acym_escape(empty($currentCtrl) ? acym_getVar('cmd', 'ctrl', '') : $currentCtrl).'"/>';
    if ($token) {
        echo acym_formToken();
    }
    echo '<button type="submit" class="is-hidden" id="formSubmit"></button>';
}

global $acymMetaData;
function acym_addMetadata($meta, $data, $name = 'name')
{
    global $acymMetaData;

    $tag = new stdClass();
    $tag->meta = $meta;
    $tag->data = $data;
    $tag->name = $name;

    $acymMetaData[] = $tag;
}

add_action('wp_head', 'acym_head_wp');
add_action('admin_head', 'acym_head_wp');
add_action('acym_head', 'acym_head_wp');
function acym_head_wp()
{
    global $acymMetaData;

    if (!empty($acymMetaData)) {
        foreach ($acymMetaData as $metadata) {
            if (empty($metadata->data)) {
                echo '<meta '.$metadata->name.'="'.acym_escape($metadata->meta).'"/>';
            } else {
                echo '<meta '.$metadata->name.'="'.acym_escape($metadata->meta).'" content="'.acym_escape($metadata->data).'"/>';
            }
        }
    }

    $acymMetaData = [];
}

function acym_includeHeaders()
{
    do_action('acym_head');
}

function acym_getOptionRegacyPosition()
{
}

function acym_renderForm(AcymParameter $params, $args = [])
{
    acym_initModule($params);

    $return = !empty($args['before_widget']) ? $args['before_widget'] : '';

    $title = apply_filters('widget_title', $params->get('title'));
    if (!empty($title)) {
        if (!empty($args['before_title'])) $return .= $args['before_title'];
        $return .= $title;
        if (!empty($args['after_title'])) $return .= $args['after_title'];
    }

    $identifiedUser = null;
    $currentUserEmail = acym_currentUserEmail();
    $userClass = new UserClass();
    if ($params->get('userinfo', '1') == '1' && !empty($currentUserEmail)) {
        $identifiedUser = $userClass->getOneByEmail($currentUserEmail);
    }

    $visibleLists = $params->get('displists', []);
    $hiddenLists = $params->get('hiddenlists', []);
    $allFields = $params->get('fields', []);
    acym_arrayToInteger($visibleLists);
    acym_arrayToInteger($hiddenLists);
    acym_arrayToInteger($allFields);

    // We need the email address
    if (!in_array(2, $allFields)) {
        $allFields[] = 2;
    }

    $listClass = new ListClass();
    $fieldClass = new FieldClass();

    $allLists = $listClass->getAllWithoutManagement(true);
    $visibleLists = array_intersect($visibleLists, array_keys($allLists));
    $hiddenLists = array_intersect($hiddenLists, array_keys($allLists));

    $allFields = $fieldClass->getFieldsByID($allFields);
    $fields = [];
    foreach ($allFields as $field) {
        if (intval($field->active) === 0) continue;
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
    $unsubButton = $params->get('unsub', '0');

    $disableButtons = !empty($args['disableButtons']) || acym_isAdmin();

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
    $displayInAPopup = 0;
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
        $js .= "\n".'acymModule["excludeValues'.$formName.'"]["'.$field->id.'"] = "'.acym_translation($field->name, true).'";';
    }
    $js .= "  });";
    // Exclude default values from fields, if the user didn't fill them in
    $return .= '<script type="text/javascript">
                '.$js.'
                </script>';

    $buttonStyle = '';
    if (!empty($params->get('button_background_color', ''))) $buttonStyle .= 'background-color: '.$params->get('button_background_color', '').';';
    if (!empty($params->get('button_text_color', ''))) $buttonStyle .= 'color: '.$params->get('button_text_color', '').';';
    if (strlen($params->get('button_border_size', '')) > 0) $buttonStyle .= 'border-width: '.$params->get('button_border_size', '').'px;';
    if (!empty($params->get('button_border_type', ''))) $buttonStyle .= 'border-style: '.$params->get('button_border_type', '').';';
    if (!empty($params->get('button_border_color', ''))) $buttonStyle .= 'border-color: '.$params->get('button_border_color', '').';';
    if (strlen($params->get('button_border_radius', '')) > 0) $buttonStyle .= 'border-radius: '.$params->get('button_border_radius', '').'px;';

    if (!empty($buttonStyle)) {
        acym_addStyle(true, '#acym_module_'.$formName.' .acysubbuttons .subbutton {'.$buttonStyle.'}');
    }

    $globalStyle = '';
    if (!empty($params->get('background_color', ''))) $globalStyle .= 'background-color: '.$params->get('background_color', '').';';
    if (!empty($params->get('text_color', ''))) $globalStyle .= 'color: '.$params->get('text_color', '').';';

    if (!empty($globalStyle)) {
        acym_addStyle(true, '#acym_module_'.$formName.', #acym_module_'.$formName.' td {'.$globalStyle.'}');
    }

    ob_start();
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
                        include ACYM_FOLDER.'widgets'.DS.'subscriptionform'.DS.'tmpl'.DS.'tableless.php';
                    } else {
                        $displayInline = $params->get('mode', 'tableless') != 'vertical';
                        include ACYM_FOLDER.'widgets'.DS.'subscriptionform'.DS.'tmpl'.DS.'default.php';
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
    $return .= ob_get_clean();

    if (!empty($args['after_widget'])) $return .= $args['after_widget'];

    return $return;
}

function acym_renderFormProfile(AcymParameter $params, $args = [])
{
    acym_initModule($params);

    if (!is_array($params->get('listsdropdown', '0'))) {
        $listDropdown = $params->get('listsdropdown', '0');
    } else {
        $listDropdown = $params->get('listsdropdown')[0];
    }

    $instance = [
        'title' => $params->get('title', ''),
        'lists' => $params->get('lists'),
        'listschecked' => $params->get('listschecked'),
        'dropdown' => $listDropdown,
        'hiddenlists' => $params->get('hiddenlists'),
        'fields' => $params->get('fields'),
        'introtext' => $params->get('introtext', ''),
        'posttext' => $params->get('posttext', ''),
        'source' => $params->get('source', ''),
    ];

    $title = apply_filters('widget_title', $instance['title']);
    if (!empty($title)) $title = '<span class="gamma widget-title">'.$title.'</span>';
    $return = $title;

    ob_start();
    acym_setVar('page', ACYM_COMPONENT.'_front');

    $userController = new FrontusersController();
    $data = $userController->prepareParams((object)$instance);
    $data['disableButtons'] = !empty($args['disableButtons']) || acym_isAdmin();

    if (empty($data['user']->language)) {
        $cmsUserLanguage = acym_getCmsUserLanguage();
        $data['user']->language = empty($cmsUserLanguage) ? acym_getLanguageTag() : $cmsUserLanguage;
    }

    acym_setVar('layout', 'profile');
    $userController->display($data);
    $return .= ob_get_clean();

    return $return;
}

function acym_renderFormArchive(AcymParameter $params, $args = [])
{
    acym_initModule($params);

    if (!is_array($params->get('popup', '1'))) {
        $popup = $params->get('popup', '1');
    } else {
        $popup = $params->get('popup')[0];
    }
    if (!is_array($params->get('displayUserListOnly', '1'))) {
        $displayUserListOnly = $params->get('displayUserListOnly', '1');
    } else {
        $displayUserListOnly = $params->get('displayUserListOnly')[0];
    }

    $instance = [
        'title' => $params->get('title', ''),
        'archiveNbNewslettersPerPage' => $params->get('archiveNbNewslettersPerPage', 20),
        'lists' => $params->get('lists'),
        'popup' => $popup,
        'displayUserListOnly' => $displayUserListOnly,
    ];

    $title = apply_filters('widget_title', $instance['title']);
    if (!empty($title)) $title = '<span class="gamma widget-title">'.$title.'</span>';
    $return = $title;
    ob_start();
    acym_setVar('page', 'front');
    $searchs = acym_getVar('array', 'acym_search', []);
    $search = '';
    if (!empty($searchs[0])) $search = $searchs[0];
    $disableButtons = !empty($args['disableButtons']) || acym_isAdmin();
    $viewParams = [
        'listsSent' => isset($instance['lists']) ? $instance['lists'] : [],
        'popup' => isset($instance['popup']) ? $instance['popup'] : '1',
        'displayUserListOnly' => isset($instance['displayUserListOnly']) ? $instance['displayUserListOnly'] : '1',
        'paramsCMS' => ['widget_id' => 0, 'suffix' => ''],
        'search' => $search,
        'disableButtons' => $disableButtons,
        'nbNewslettersPerPage' => $instance['archiveNbNewslettersPerPage'],
    ];

    $archiveController = new ArchiveController();
    $archiveController->showArchive($viewParams);

    $return .= ob_get_clean();

    return $return;
}
