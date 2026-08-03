<?php

defined('ABSPATH') || die('Restricted Access');

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
            // TODO: refactor js insertion to use CMS dedicated methods
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw JS content passed by caller.
            echo '<script type="text/javascript">'.$script.'</script>';
        }
    } elseif (!empty($params['defer']) || !empty($params['async']) || !empty($params['needTagScript'])) {
        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- After WordPress hook.
        echo '<script type="text/javascript" src="'.acym_escapeUrl($script).'"'.(!empty($params['async']) ? ' async' : '').(!empty($params['defer']) ? ' defer' : '').'></script>';
    } else {
        wp_enqueue_script(
            $handle,
            $script,
            $params['dependencies'],
            '{__VERSION__}',
            [
                'in_footer' => false,
            ]
        );
    }

    return $handle;
}

function acym_addStyle(bool $raw, string $style): void
{
    // TODO: refactor style insertion to use CMS dedicated methods

    if ($raw) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw CSS content passed by caller.
        echo '<style>'.$style.'</style>';
    } else {
        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- To refactor.
        echo '<link rel="stylesheet" href="'.acym_escapeUrl($style).'" type="text/css">';
    }
}

function acym_loadCmsScripts(): void
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

function acym_redirect(string $url, string $msg = '', string $msgType = 'message', bool $safe = true): void
{
    if (acym_isAdmin() && substr($url, 0, 4) != 'http' && substr($url, 0, 4) != 'www.') {
        $url = acym_addPageParam($url);
    }

    if (empty($url)) {
        $url = acym_rootURI();
    }

    $wordpressOutput = @ob_get_clean();
    if (headers_sent()) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped output from WordPress itself.
        echo $wordpressOutput;
        acym_addScript(true, 'window.location.href = '.wp_json_encode($url).';');
    } else {
        if ($safe) {
            wp_safe_redirect($url);
        } else {
            // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- mainly for email links after tracking is applied
            wp_redirect($url);
        }
    }
    exit;
}
