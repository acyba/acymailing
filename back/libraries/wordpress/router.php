<?php

function acym_addScript($raw, $script, $type = 'text/javascript', $defer = false, $async = false, $needTagScript = false, $deps = ['jquery'])
{
    static $scriptNumber = 0;
    $scriptNumber++;
    if ($raw) {
        if (!empty($deps['script_name'])) {
            wp_add_inline_script($deps['script_name'], $script);
        } else {
            echo '<script type="'.$type.'">'.$script.'</script>';
        }
    } elseif ($defer || $async || $needTagScript) {
        echo '<script type="'.$type.'" src="'.$script.'"'.($async ? ' async' : '').($defer ? ' defer' : '').'></script>';
    } else {
        wp_enqueue_script('script'.$scriptNumber, $script, $deps);
    }

    return 'script'.$scriptNumber;
}

function acym_addStyle($raw, $style, $type = 'text/css', $media = null, $attribs = [])
{
    if ($raw) {
        echo '<style type="'.$type.'"'.(empty($media) ? '' : ' media="'.$media.'"').'>'.$style.'</style>';
    } else {
        echo '<link rel="stylesheet" href="'.$style.'" type="'.$type.'"'.(empty($media) ? '' : ' media="'.$media.'"').'>';
    }
}

function acym_prepareFrontViewDisplay($ctrl, $task)
{
    if (acym_isAdmin()) return;

    $config = acym_config();
    if ($ctrl === 'frontusers' && $task === 'unsubscribepage' && $config->get('unsubpage_header', 0) == 1) get_header();
}

function acym_loadCmsScripts()
{
    $toggleController = acym_isAdmin() ? 'toggle' : 'fronttoggle';
    acym_addScript(
        true,
        'var ACYM_TOGGLE_URL = "admin-ajax.php?action='.ACYM_COMPONENT.'_router&'.acym_noTemplate().'&page='.ACYM_COMPONENT.'_toggle&ctrl='.$toggleController.'&'.acym_getFormToken(
        ).'";
        var ACYM_AJAX_URL = "admin-ajax.php?action='.ACYM_COMPONENT.'_router&'.acym_noTemplate().'&'.acym_getFormToken().'";
        var ACYM_IS_ADMIN = '.(acym_isAdmin() ? 'true' : 'false').';

        if("undefined" === typeof icl_ajxloaderimg_src) var icl_ajxloaderimg_src = "";'
    );

    // Without this line the image insertion and dtexts button don't work
    wp_enqueue_media();

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-effects-slide');
}

function acym_redirect($url, $msg = '', $msgType = 'message')
{
    if (acym_isAdmin() && substr($url, 0, 4) != 'http' && substr($url, 0, 4) != 'www.') {
        $url = acym_addPageParam($url);
    }
    @ob_get_clean();
    if (empty($url)) $url = acym_rootURI();
    if (headers_sent()) {
        acym_addScript(true, 'window.location.href = "'.addslashes($url).'";');
    } else {
        wp_redirect($url);
    }
    exit;
}
