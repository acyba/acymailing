<?php
// context verification

function acym_getEmailCssFixes(): string
{
    $emailFixes = acym_fileGetContent(ACYM_MEDIA.'css'.DS.'email.min.css');

    $config = acym_config();
    if ('1' === $config->get('prevent_hyphens', '')) {
        $emailFixes .= 'table td.acym__wysid__column__element__td, table td.acym__wysid__column__element__td p { word-break: break-word; -webkit-hyphens: none; -moz-hyphens: none; hyphens: none; mso-hyphenate: none; }';
    }

    return $emailFixes;
}

function acym_getMailThumbnail(?string $thumbnail): string
{
    $sources = [
        '',
        ACYM_TEMPLATE_THUMBNAILS,
        ACYM_IMAGES.'thumbnails/',
    ];

    if (!empty($thumbnail)) {
        foreach ($sources as $oneSource) {
            if (file_exists(str_replace(acym_rootURI(), ACYM_ROOT, $oneSource.$thumbnail))) {
                return $oneSource.$thumbnail;
            }
        }
    }

    return ACYM_IMAGES.'templates/default_template_thumbnail.png';
}

function acym_mailContentInput(string $body, string $id = '', string $class = ''): void
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The body is double-encoded here, as the JS reading this attribute injects it as HTML.
    echo '<input value="'.htmlspecialchars($body, ENT_QUOTES, 'UTF-8').'"
        type="hidden"
        '.(empty($id) ? '' : ' id="'.acym_escape($id).'"').' 
        '.(empty($class) ? '' : ' class="'.acym_escape($class).'"').'>';
}

function acym_getFlagByCode(string $code): string
{
    $code = explode('-', $code);
    $finalCode = '';

    foreach ($code as $part) {
        $part = strtolower($part);
        if (file_exists(ACYM_MEDIA.'images'.DS.'flags'.DS.$part.'.png')) {
            $finalCode = $part;
        }
    }

    if (empty($finalCode)) {
        $finalCode = 'unknown';
    }

    return ACYM_IMAGES.'flags/'.$finalCode.'.png';
}
