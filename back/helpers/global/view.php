<?php

function acym_getView($ctrl, $view, $forceBackend = false)
{
    // Handle page override
    $override = acym_getPageOverride($ctrl, $view, $forceBackend);

    if (!empty($override) && file_exists($override)) {
        return $override;
    } else {
        $viewsFolder = ($forceBackend || acym_isAdmin()) ? ACYM_VIEW : ACYM_VIEW_FRONT;

        return $viewsFolder.$ctrl.DS.'tmpl'.DS.$view.'.php';
    }
}

function acym_getPartial($family, $view)
{
    return ACYM_PARTIAL.$family.DS.$view.'.php';
}

function acym_loadAssets($ctrl, $task)
{
    $scope = acym_isAdmin() ? 'back' : 'front';
    acym_loadCmsScripts();

    acym_addScript(
        true,
        'var AJAX_URL_UPDATEME = "'.ACYM_UPDATEMEURL.'";
        var ACYM_MEDIA_URL = "'.ACYM_MEDIA_URL.'";
        var ACYM_CMS = "'.ACYM_CMS.'";
        var ACYM_J40 = '.(defined('ACYM_J40') && ACYM_J40 ? 'true' : 'false').';
        var FOUNDATION_FOR_EMAIL = "'.ACYM_CSS.'libraries/foundation_email.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'foundation_email.min.css').'";
        var ACYM_FIXES_FOR_EMAIL = "'.str_replace('"', '\"', acym_getEmailCssFixes()).'";
        var ACYM_REGEX_EMAIL = /^'.acym_getEmailRegex(true).'$/i;
        var ACYM_JS_TXT = '.acym_getJSMessages().';
        var ACYM_CORE_DYNAMICS_URL = "'.ACYM_CORE_DYNAMICS_URL.'";
        var ACYM_PLUGINS_URL = "'.addslashes(ACYM_PLUGINS_URL).'";
        var ACYM_ROOT_URI = "'.acym_rootURI().'";
        var ACYM_CONTROLLER = "'.$ctrl.'";
        var ACYM_SOCIAL_MEDIA = "'.addslashes(ACYM_SOCIAL_MEDIA).'";'
    );

    //TODO get rid of the defer option on all these scripts to make sure jquery is loaded on front and fix the js errors
    // We added this defer to make sure the raw js above is loaded first, use a dependency instead.
    acym_addScript(false, ACYM_JS.'helpers.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'helpers.min.js'), 'text/javascript', true);

    if ($ctrl !== 'archive') {
        acym_addScript(
            false,
            ACYM_JS.'libraries/foundation.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'foundation.min.js'),
            'text/javascript',
            true
        );
    }

    if ('back' === $scope) {
        acym_addScript(false, ACYM_JS.'libraries/select2.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'select2.min.js'), 'text/javascript', true);
    }

    // Include JS
    if ('back' == $scope) acym_addScript(false, ACYM_JS.$scope.'_helpers.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.$scope.'_helpers.min.js'), 'text/javascript', true);
    acym_addScript(false, ACYM_JS.'global.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'global.min.js'), 'text/javascript', true);
    acym_addScript(false, ACYM_JS.$scope.'_global.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.$scope.'_global.min.js'), 'text/javascript', true);

    if (file_exists(ACYM_MEDIA.'js'.DS.$scope.DS.$ctrl.'.min.js')) {
        acym_addScript(false, ACYM_JS.$scope.'/'.$ctrl.'.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.$scope.DS.$ctrl.'.min.js'), 'text/javascript', true);
    }
    if (file_exists(ACYM_MEDIA.'js'.DS.$scope.DS.$ctrl.DS.$task.'.min.js')) {
        acym_addScript(false, ACYM_JS.$scope.'/'.$ctrl.'/'.$task.'.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.$scope.DS.$ctrl.DS.$task.'.min.js'), 'text/javascript', true);
    }

    acym_addStyle(false, ACYM_CSS.'global.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'global.min.css'));

    // Include CSS
    if (!acym_isExcludedFrontView($ctrl, $task)) {
        acym_addStyle(false, ACYM_CSS.$scope.'_global.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.$scope.'_global.min.css'));
    }

    if (file_exists(ACYM_MEDIA.'css'.DS.$scope.DS.$ctrl.'.min.css')) {
        acym_addStyle(false, ACYM_CSS.$scope.'/'.$ctrl.'.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.$scope.DS.$ctrl.'.min.css'));
    }
}

/**
 * Get translated error message for JS form validation
 * @return string containing texts (js object formated)
 */
function acym_getJSMessages()
{
    $msg = "{";
    $msg .= '"email": "'.acym_translation('ACYM_VALID_EMAIL', true).'",';
    $msg .= '"number": "'.acym_translation('ACYM_VALID_NUMBER', true).'",';
    $msg .= '"requiredMsg": "'.acym_translation('ACYM_REQUIRED_FIELD', true).'",';
    $msg .= '"defaultMsg": "'.acym_translation('ACYM_DEFAULT_VALIDATION_ERROR', true).'"';

    $keysToLoad = [
        'ACYM_ACTIVATED',
        'ACYM_DEACTIVATED',
        'ACYM_NOT_ENABLED_YET',
        'ACYM_LICENSE_ACTIVATED',
        'ACYM_SELECT_FIELD',
        'ACYM_NO_FIELD_AVAILABLE',
        'ACYM_SUBMIT_AND_DEACTIVATE',
        'ACYM_SKIP_AND_DEACTIVATE',
        'ACYM_SAVE',
        'ACYM_ARE_YOU_SURE',
        'ACYM_INSERT_IMG_BAD_NAME',
        'ACYM_NON_VALID_URL',
        'ACYM_ARE_YOU_SURE_DELETE',
        'ACYM_ARE_YOU_SURE_DELETE_ONE',
        'ACYM_ARE_YOU_SURE_ACTIVE',
        'ACYM_ARE_YOU_SURE_INACTIVE',
        'ACYM_SEARCH',
        'ACYM_CANCEL',
        'ACYM_CONFIRM',
        'ACYM_TEMPLATE_CHANGED_CLICK_ON_SAVE',
        'ACYM_SURE_SEND_TRANSALTION',
        'ACYM_TESTS_SPAM_SENT',
        'ACYM_CONFIRMATION_CANCEL_CAMPAIGN_QUEUE',
        'ACYM_EXPORT_SELECT_LIST',
        'ACYM_YES',
        'ACYM_NO',
        'ACYM_NEXT',
        'ACYM_BACK',
        'ACYM_SKIP',
        'ACYM_INTRO_DRAG_CONTENT',
        'ACYM_SEND_TEST_SUCCESS',
        'ACYM_SEND_TEST_ERROR',
        'ACYM_COPY_DEFAULT_TRANSLATIONS_CONFIRM',
        'ACYM_BECARFUL_BACKGROUND_IMG',
        'ACYM_CANT_DELETE_AND_SAVE',
        'ACYM_AND',
        'ACYM_OR',
        'ACYM_ERROR',
        'ACYM_EDIT_MAIL',
        'ACYM_CREATE_MAIL',
        'ACYM_NO_RAND_FOR_MULTQUEUE',
        'ACYM_DELETE_MY_DATA_CONFIRM',
        'ACYM_CHOOSE_COLUMN',
        'ACYM_AUTOSAVE_USE',
        'ACYM_SELECT_NEW_ICON',
        'ACYM_SESSION_IS_GOING_TO_END',
        'ACYM_CLICKS_OUT_OF',
        'ACYM_OF_CLICKS',
        'ACYM_ARE_SURE_DUPLICATE_TEMPLATE',
        'ACYM_NOT_FOUND',
        'ACYM_EMAIL',
        'ACYM_ERROR_SAVING',
        'ACYM_LOADING_ERROR',
        'ACYM_AT_LEAST_ONE_USER',
        'ACYM_NO_DCONTENT_TEXT',
        'ACYM_PREVIEW',
        'ACYM_PREVIEW_DESC',
        'ACYM_CONTENT_TYPE',
        'ACYM_TEMPLATE_EMPTY',
        'ACYM_DRAG_BLOCK_AND_DROP_HERE',
        'ACYM_WELL_DONE_DROP_HERE',
        'ACYM_REPLACE_CONFIRM',
        'ACYM_STATS_START_DATE_LOWER',
        'ACYM_ARE_YOU_SURE_DELETE_ADD_ON',
        'ACYM_COULD_NOT_SUBMIT_FORM_CONTACT_ADMIN_WEBSITE',
        'ACYM_TEMPLATE_CREATED',
        'ACYM_UNSUBSCRIBE',
        'ACYM_BUTTON',
        'ACYM_SPACE_BETWEEN_BLOCK',
        'ACYM_X1_AND_X2',
        'ACYM_COULD_NOT_SAVE_THUMBNAIL_ERROR_X',
        'ACYM_REQUEST_FAILED_TIMEOUT',
        'ACYM_INSERT_DYNAMIC_TEXT',
        'ACYM_PLEASE_SET_A_LICENSE_KEY',
        'ACYM_COULD_NOT_UPLOAD_CSV_FILE',
        'ACYM_RESET_VIEW_CONFIRM',
        'ACYM_FILL_ALL_INFORMATION',
        'ACYM_ASSIGN_EMAIL_COLUMN',
        'ACYM_DUPLICATE_X_FOR_X',
        'ACYM_ASSIGN_COLUMN_TO_FIELD',
        'ACYM_SEARCH_FOR_GIFS',
        'ACYM_NO_RESULTS_FOUND',
        'ACYM_SEARCH_GIFS',
        'ACYM_COULD_NOT_LOAD_GIF_TRY_FEW_MINUTES',
        'ACYM_GIPHY_LOW_RES_TEXT',
        'ACYM_DONT_APPLY_STYLE_TAG_A',
        'ACYM_TITLE',
        'ACYM_PRICE',
        'ACYM_SHORT_DESCRIPTION',
        'ACYM_DESCRIPTION',
        'ACYM_CATEGORIES',
        'ACYM_DETAILS',
        'ACYM_LINK',
        'ACYM_INTRO_TEXT',
        'ACYM_FULL_TEXT',
        'ACYM_CATEGORY',
        'ACYM_PUBLISHING_DATE',
        'ACYM_READ_MORE',
        'ACYM_IMAGE',
        'ACYM_DOWNLOAD',
        'ACYM_INTRO_ONLY',
        'ACYM_LINK_DOWNLOAD',
        'ACYM_IMAGE_HTML_TAG',
        'ACYM_LOCATION',
        'ACYM_TAGS',
        'ACYM_START_DATE',
        'ACYM_END_DATE',
        'ACYM_FEATURED_IMAGE',
        'ACYM_DATE',
        'ACYM_START_DATE',
        'ACYM_START_DATE_SIMPLE',
        'ACYM_END_DATE_SIMPLE',
        'ACYM_AUTHOR',
        'ACYM_SHOW_FILTERS',
        'ACYM_HIDE_FILTERS',
        'ACYM_PLEASE_FILL_FORM_NAME',
        'ACYM_SELECT',
        'ACYM_CHANGE',
        'ACYM_ENTER_SUBJECT',
        'ACYM_REMOVE_LANG_CONFIRMATION',
        'ACYM_RESET_TRANSLATION',
        'ACYM_MAX_EXEC_TIME_GET_ERROR',
        'ACYM_SAVE_AS_TEMPLATE_CONFIRMATION',
        'ACYM_VERTICAL_PADDING',
        'ACYM_HORIZONTAL_PADDING',
        'ACYM_VERTICAL_PADDING_DESC',
        'ACYM_HORIZONTAL_PADDING_DESC',
        'ACYM_SELECT_A_PICTURE',
        'ACYM_PLEASE_SELECT_FILTERS',
        'ACYM_DELETE_THIS_FILTER',
        'ACYM_IF_YOU_SELECT_SEGMENT_FILTERS_ERASE',
        'ACYM_PLEASE_FILL_A_NAME_FOR_YOUR_SEGMENT',
        'ACYM_COULD_NOT_SAVE_SEGMENT',
        'ACYM_SENT',
        'ACYM_RECIPIENTS',
        'ACYM_RESET_OVERRIDES_CONFIRMATION',
        'ACYM_OPEN_PERCENTAGE',
        'ACYM_MONDAY',
        'ACYM_TUESDAY',
        'ACYM_WEDNESDAY',
        'ACYM_THURSDAY',
        'ACYM_FRIDAY',
        'ACYM_SATURDAY',
        'ACYM_SUNDAY',
        'ACYM_SELECT2_RESULTS_NOT_LOADED',
        'ACYM_SELECT2_DELETE_X_CHARACTERS',
        'ACYM_SELECT2_ENTER_X_CHARACTERS',
        'ACYM_SELECT2_LOADING_MORE_RESULTS',
        'ACYM_SELECT2_LIMIT_X_ITEMS',
        'ACYM_SELECT2_SEARCHING',
        'ACYM_JANUARY',
        'ACYM_FEBRUARY',
        'ACYM_MARCH',
        'ACYM_APRIL',
        'ACYM_MAY',
        'ACYM_JUNE',
        'ACYM_JULY',
        'ACYM_AUGUST',
        'ACYM_SEPTEMBER',
        'ACYM_OCTOBER',
        'ACYM_NOVEMBER',
        'ACYM_DECEMBER',
        'ACYM_NEW_SUBSCRIBERS',
        'ACYM_NEW_UNSUBSCRIBERS',
        'ACYM_DEFAULT',
        'ACYM_SELECT_IMAGE_TO_UPLOAD',
        'ACYM_USE_THIS_IMAGE',
        'ACYM_BACKGROUND_COLOR',
        'ACYM_BACKGROUND_COLOR_DESC',
        'ACYM_START_HOUR',
        'ACYM_END_HOUR',
        'ACYM_START_MINUTES',
        'ACYM_END_MINUTES',
        'ACYM_START_AM_PM',
        'ACYM_END_AM_PM',
        'ACYM_START_DAY_TIME',
        'ACYM_END_DAY_TIME',
        'ACYM_LOCATION_URL',
        'ACYM_LATITUDE_LONGITUDE',
        'ACYM_ADDRESS',
        'ACYM_NO_FILE_CHOSEN',
        'ACYM_BEFORE_FIELDS',
        'ACYM_AFTER_FIELDS',
        'ACYM_MODE_HORIZONTAL',
        'ACYM_MODE_VERTICAL',
        'ACYM_MODE_TABLELESS',
        'ACYM_TEXT_INSIDE',
        'ACYM_TEXT_OUTSIDE',
        'ACYM_NO',
        'ACYM_CONNECTED_USER_SUBSCRIBED',
        'ACYM_SUCCESS_REPLACE',
        'ACYM_SUCCESS_REPLACE_TEMP',
        'ACYM_SUCCESS_TOP_TEMP',
        'ACYM_SUCCESS_STANDARD',
        'ACYM_LEFT',
        'ACYM_CENTER',
        'ACYM_RIGHT',
        'ACYM_IN_HEADER',
        'ACYM_ON_THE_MODULE',
        'ACYM_ACYMAILING_SUBSCRIPTION_FORM',
        'ACYM_MAIN_OPTIONS',
        'ACYM_DISPLAY_MODE',
        'ACYM_SUBSCRIBE_TEXT',
        'ACYM_SUBSCRIBE_TEXT_LOGGED_IN',
        'ACYM_AUTO_SUBSCRIBE_TO',
        'ACYM_DISPLAYED_LISTS',
        'ACYM_LISTS_CHECKED_DEFAULT',
        'ACYM_DISPLAY_LISTS',
        'ACYM_LISTS_OPTIONS',
        'ACYM_FIELDS_OPTIONS',
        'ACYM_FIELDS_TO_DISPLAY',
        'ACYM_TEXT_MODE',
        'ACYM_TERMS_POLICY_OPTIONS',
        'ACYM_TERMS_CONDITIONS',
        'ACYM_PRIVACY_POLICY',
        'ACYM_SUBSCRIBE_OPTIONS',
        'ACYM_SUCCESS_MODE',
        'ACYM_CONFIRMATION_MESSAGE',
        'ACYM_UNSUBSCRIBE_OPTIONS',
        'ACYM_DISPLAY_UNSUB_BUTTON',
        'ACYM_UNSUBSCRIBE_TEXT',
        'ACYM_REDIRECT_LINK_UNSUB',
        'ACYM_ADVANCED_OPTIONS',
        'ACYM_POST_TEXT',
        'ACYM_ALIGNMENT',
        'ACYM_MODULE_JS',
        'ACYM_SOURCE',
        'ACYM_FORM_AUTOFILL_ID',
        'ACYM_REDIRECT_LINK',
        'ACYM_DKIM_KEY',
        'ACYM_VALUE',
        'ACYM_CF_VALUE_CHANGED',
        'ACYM_OLD_VALUE',
        'ACYM_NEW_VALUE',
        'ACYM_CUSTOM_FIELDS',
        'ACYM_CUSTOM_VIEW_EDITOR_DESC',
        'ACYM_PREVIEW_CUSTOM_VIEW',
        'ACYM_ORGANIZER',
        'ACYM_ACYMAILING_PROFILE_FORM',
        'ACYM_VISIBLE_LISTS',
        'ACYM_DROPDOWN_LISTS',
        'ACYM_ACYMAILING_ARCHIVE_FORM',
        'ACYM_ARCHIVE_POPUP',
        'ACYM_ARCHIVE_ONLY_USER_LIST',
        'ACYM_ZONE_NAME',
        'ACYM_ZONE_SAVE_TEXT',
        'ACYM_NEW_CUSTOM_ZONE',
        'ACYM_INSERT',
        'ACYM_LOAD',
        'ACYM_CONFIRM_DELETION_ZONE',
        'ACYM_WIDGET_CAMPAIGN_NUMBER_PER_PAGE',
        'ACYM_OTHER_ORGANIZER',
        'ACYM_OTHER_LOCATION',
        'ACYM_NEXT_OCCURRENCES',
        'ACYM_CONDITIONS_AND_FILTERS_WILL_BE_DELETED',
    ];

    foreach ($keysToLoad as $oneKey) {
        $msg .= ',"'.$oneKey.'": "'.acym_translation($oneKey, true).'"';
    }

    $msg .= "}";

    return $msg;
}

function acym_isExcludedFrontView($ctrl, $task)
{
    if ('archive' === $ctrl && in_array($task, ['view'])) return true;
    if ('frontusers' === $ctrl && 'profile' === $task) return true;

    return false;
}

function acym_listingActions($actions, $deleteMessage = '', $ctrl = '')
{
    $defaultAction = new stdClass();
    $defaultAction->value = 0;
    $defaultAction->text = acym_translation('ACYM_CHOOSE_ACTION');
    $defaultAction->disable = true;

    array_unshift($actions, $defaultAction);

    $completeMessage = '<input id="acym__listing__action__delete-message" value="'.(empty($deleteMessage) ? '' : acym_escape($deleteMessage)).'" type="hidden">';

    $attrCtrl = '';
    if (!empty($ctrl)) {
        $attrCtrl = 'data-ctrl="'.$ctrl.'"';
        $completeMessage .= ' <input type="hidden" name="return_listing">';
    }

    return acym_select($actions, '', null, 'class="medium-shrink cell margin-right-1" '.$attrCtrl, 'value', 'text', 'listing_actions').$completeMessage;
}

/**
 * @param $listingName
 *
 * @return string
 */
function acym_backToListing($listingName = null)
{
    if (empty($listingName)) $listingName = acym_getVar('cmd', 'ctrl');

    $returnLink = '<p class="acym__back_to_listing">';
    $returnLink .= '<a href="'.acym_completeLink($listingName).'" class="acym_vcenter">';
    $returnLink .= '<i class="acymicon-chevron-left"></i> '.acym_translation('ACYM_BACK_TO_LISTING');
    $returnLink .= '</a>';
    $returnLink .= '</p>';

    return $returnLink;
}
