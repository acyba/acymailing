<?php

function acym_getEmailCssFixes()
{
    $emailFixes = acym_fileGetContent(ACYM_MEDIA.'css'.DS.'email.min.css');

    $config = acym_config();
    if ('1' === $config->get('prevent_hyphens', '')) {
        $emailFixes .= 'table td.acym__wysid__column__element__td, table td.acym__wysid__column__element__td p { word-break: keep-all !important; hyphens: none !important; }';
    }

    return $emailFixes;
}

function acym_getMailThumbnail($thumbnail)
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

    return ACYM_IMAGES.'default_template_thumbnail.png';
}
