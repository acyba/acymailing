<?php

function acym_addScript(bool $raw, string $script, array $params = []): string
{
    static $scriptNumber = 0;
    $scriptNumber++;
    $handle = 'acym_script'.$scriptNumber;

    if (!isset($params['dependencies'])) {
        $params['dependencies'] = ['jquery'];
    }

    if ($raw) {
        if (!empty($params['dependencies']['script_name'])) {
            wp_add_inline_script($params['dependencies']['script_name'], $script);
        } else {
            echo '<script type="text/javascript">'.$script.'</script>';
        }
    } elseif (!empty($params['defer']) || !empty($params['async']) || !empty($params['needTagScript'])) {
        echo '<script type="text/javascript" src="'.$script.'"'.(!empty($params['async']) ? ' async' : '').(!empty($params['defer']) ? ' defer' : '').'></script>';
    } else {
        wp_enqueue_script($handle, $script, $params['dependencies']);
    }

    return $handle;
}

function acym_addStyle(bool $raw, string $style)
{
    if ($raw) {
        echo '<style>'.$style.'</style>';
    } else {
        echo '<link rel="stylesheet" href="'.$style.'" type="text/css">';
    }
}

function acym_prepareFrontViewDisplay($ctrl, $task)
{
    if (acym_isAdmin()) return;

    $config = acym_config();
    if ($ctrl === 'frontusers' && $task === 'unsubscribepage' && $config->get('unsubpage_header', 0) == 1) {
        get_header();
    }
}

function acym_loadCmsScripts()
{
    $toggleController = acym_isAdmin() ? 'toggle' : 'fronttoggle';
    acym_addScript(
        true,
        '
        var ACYM_AJAX_URL = "'.admin_url('admin-ajax.php').'?action='.ACYM_COMPONENT.'_router&'.acym_noTemplate().'&'.acym_getFormToken().'&nocache='.time().'";
        var ACYM_TOGGLE_URL = ACYM_AJAX_URL + "&page='.ACYM_COMPONENT.'_toggle&ctrl='.$toggleController.'";
        var ACYM_IS_ADMIN = '.(acym_isAdmin() ? 'true' : 'false').';
        if("undefined" === typeof icl_ajxloaderimg_src) var icl_ajxloaderimg_src = "";'
    );

    // Without this line the image insertion and dtexts button doesn't work
    wp_enqueue_media();

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-effects-slide');
}

function acym_redirect($url, $msg = '', $msgType = 'message', $safe = false)
{
    if (acym_isAdmin() && substr($url, 0, 4) != 'http' && substr($url, 0, 4) != 'www.') {
        $url = acym_addPageParam($url);
    }
    @ob_get_clean();
    if (empty($url)) $url = acym_rootURI();
    if (headers_sent()) {
        acym_addScript(true, 'window.location.href = "'.addslashes($url).'";');
    } else {
        if ($safe) {
            wp_safe_redirect($url);
        } else {
            wp_redirect($url);
        }
    }
    exit;
}
