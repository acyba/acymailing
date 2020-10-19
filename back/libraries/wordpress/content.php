<?php

function acym_cmsModal($isIframe, $content, $buttonText, $isButton, $identifier = null, $width = '800', $height = '400')
{
    // Use the WP's thickbox library
    add_thickbox();

    $class = $isButton ? ' button' : '';

    if ($isIframe) {
        return '<a href="'.$content.'&TB_iframe=true&width='.$width.'&height='.$height.'" class="thickbox'.$class.'">'.acym_translation($buttonText).'</a>';
    } else {
        if (empty($identifier)) {
            $identifier = 'identifier_'.rand(1000, 9000);
        }

        return '<div id="'.$identifier.'" style="display:none;">'.$content.'</div>
                <a href="#TB_inline?width='.$width.'&height='.$height.'&inlineId='.$identifier.'" class="thickbox'.$class.'">'.acym_translation($buttonText).'</a>';
    }
}

function acym_CMSArticleTitle($id)
{
    return acym_loadResult('SELECT post_title FROM #__posts WHERE ID = '.intval($id));
}

function acym_getArticleURL($id, $popup, $text)
{
    if (empty($id)) return '';

    $url = get_permalink($id);

    if ($popup == 1) {
        $url .= (strpos($url, '?') ? '&' : '?').acym_noTemplate();
        $url = acym_cmsModal(true, $url, $text, false);
    } else {
        $url = '<a title="'.acym_translation($text, true).'" href="'.acym_escape($url).'" target="_blank">'.acym_translation($text).'</a>';
    }

    return $url;
}

function acym_articleSelectionPage()
{
    return 'admin-ajax.php?action=acymailing_router&page=acymailing_configuration&ctrl=configuration&task=getarticles&'.acym_getFormToken();
}

function acym_getPageOverride($ctrl, $view)
{
    return ACYM_OVERRIDES.$ctrl.DS.$view.'.php';
}

function acym_cmsCleanHtml($html)
{
    if (strpos($html, '<!-- wp:') === false) return $html;

    // Replace special WP content in inserted posts and pages
    $elementsToRemove = [
        'shortcode',
        'core-embed/.*',
        'video .*',
        'audio .*',
    ];

    $replacements = [
        '#<!-- wp:core-embed/vimeo.*"url":"([^"]+)".+<!-- /wp:core-embed/vimeo -->#Uis' => '{vimeo}$1{/vimeo}',
        '#<!-- wp:core-embed/youtube.*"url":"([^"]+)".+<!-- /wp:core-embed/youtube -->#Uis' => '{youtube}$1{/youtube}',
        '#<a [^>]*wp-block-file__button[^>]*>[^<]*</a>#Uis' => '',
    ];

    foreach ($elementsToRemove as $oneElement) {
        $replacements['#<!-- wp:'.$oneElement.' -->.*<!-- /wp:'.$oneElement.' -->#Uis'] = '';
    }

    $cleanText = preg_replace(array_keys($replacements), $replacements, $html);
    if (!empty($cleanText)) $html = $cleanText;

    // Display the WP content correctly
    $html .= '<style type="text/css">
        /* Handle media-text blocks */
        .wp-block-media-text {
            display: grid;
            grid-template-rows: auto;
            align-items: center;
            grid-template-areas: "media-text-media media-text-content";
            grid-template-columns: 50% auto;
        }
        .wp-block-media-text .wp-block-media-text__media {
            grid-area: media-text-media;
            margin: 0;
        }
        .wp-block-media-text .wp-block-media-text__content {
            word-break: break-word;
            grid-area: media-text-content;
            padding: 0 8%;
        }

        /* Handle multi column blocks */
        .wp-block-columns {
            display: flex !important;
            flex-wrap: nowrap;
        }
        .wp-block-columns .wp-block-column {
            flex-basis: 100%;
            flex-grow: 0;
        }

        /* Handle WP tables */
        table.wp-block-table td {
            padding: 1em 1.41575em !important;
        }

        /* Handle preformatted content */
        .wp-block-preformatted, .wp-block-code, .wp-block-verse {
            padding: 1.618em;
        }

        /* Handle download files */
        .wp-block-file {
            margin: 20px 0;
        }

        /* Handle cover blocks */
        .wp-block-cover, .wp-block-cover-image {
            -webkit-box-orient: horizontal;
            -webkit-box-direction: normal;
            -webkit-flex-flow: row wrap;
            flex-flow: row wrap;
            position: relative;
            background-color: #000;
            background-size: cover;
            background-position: 50%;
            min-height: 430px;
            width: 100%;
            margin: 0 0 1.5em;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .wp-block-cover-image.has-background-dim:before, .wp-block-cover.has-background-dim:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-color: inherit;
            opacity: .5;
            z-index: 1;
        }
        .wp-block-cover p {
            font-size: 1.618em;
            font-weight: 300;
            line-height: 1.618;
            padding: 1em;
            color: #fff !important;
            z-index: 1;
        }

        /* Handle galleries */
        .wp-block-gallery {
            margin: 0 0 1.41575em;
            display: flex;
            flex-wrap: wrap;
            list-style-type: none;
            padding: 0;
        }
        .blocks-gallery-item {
            margin-left: auto;
            margin-right: auto;
        }
        </style>';

    return $html;
}

function acym_getAlias($name)
{
    return sanitize_title_with_dashes(remove_accents($name));
}

function acym_getAllPages()
{
    $allPges = get_pages();
    if (empty($allPges)) return [];

    $return = [];

    foreach ($allPges as $page) {
        $return[$page->ID] = $page->post_title;
    }

    return $return;
}

function acym_getArticles($search)
{
    $return = [];

    $search_results = new WP_Query(
        [
            's' => $search,
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'post_type' => ['page', 'post'],
            'posts_per_page' => 20,
        ]
    );

    if ($search_results->have_posts()) {
        while ($search_results->have_posts()) {
            $search_results->the_post();
            $return[] = [$search_results->post->ID, $search_results->post->post_title];
        }
    }

    return $return;
}

function acym_getArticleById($id)
{
    $post = get_post($id);

    if (empty($post)) return [];

    return [
        'id' => $post->ID,
        'title' => $post->post_title,
    ];
}
