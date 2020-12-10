<?php

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
    }
    acym_setVar('_wpnonce', $token);

    return '_wpnonce='.$token;
}

function acym_noTemplate($component = true)
{
    return 'noheader=1';
}

function acym_isNoTemplate()
{
    return acym_getVar('cmd', 'noheader') == '1';
}

function acym_setNoTemplate($status = true)
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
 */
function acym_formOptions($token = true, $task = '', $currentStep = null, $currentCtrl = '')
{
    if (!empty($currentStep)) {
        echo '<input type="hidden" name="step" value="'.$currentStep.'"/>';
        echo '<input type="hidden" name="nextstep" value=""/>';
    }
    echo '<input type="hidden" name="task" value="'.$task.'"/>';
    echo '<input type="hidden" name="nextstep" value=""/>';
    echo '<input type="hidden" name="page" value="'.acym_getVar('cmd', 'page', '').'"/>';
    echo '<input type="hidden" name="ctrl" value="'.(empty($currentCtrl) ? acym_getVar('cmd', 'ctrl', '') : $currentCtrl).'"/>';
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
            if (empty($metadata->data)) continue;
            echo '<meta '.$metadata->name.'="'.acym_escape($metadata->meta).'" content="'.acym_escape($metadata->data).'"/>';
        }
    }

    $acymMetaData = [];
}

function acym_includeHeaders()
{
    do_action('acym_head');
}
