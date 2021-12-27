<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymRss extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = 'RSS';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.svg';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'enclosure' => ['ACYM_IMAGE', true],
                'desc' => ['ACYM_DESCRIPTION', true],
                'author' => ['ACYM_AUTHOR', false],
                'date' => ['ACYM_PUBLISHING_DATE', false],
            ];

            $this->initReplaceOptionsCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions),
                ],
            ];

            if (ACYM_CMS == 'joomla') {
                $this->settings['front'] = [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => [
                        'all' => 'ACYM_ALL_ELEMENTS',
                        'hide' => 'ACYM_DONT_SHOW',
                    ],
                ];
            }
        }
    }

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = '';
        $format->imagePath = '{enclosure}';
        $format->description = '{desc}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
        ];
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        $displayOptions = [
            [
                'title' => 'ACYM_URL',
                'type' => 'text',
                'name' => 'url',
                'placeholder' => 'https://www...',
                'class' => ' ',
                'main' => true,
            ],
            [
                'title' => 'ACYM_FIELDS_TO_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $this->displayOptions,
                'separator' => ',',
            ],
            [
                'title' => 'ACYM_DISPLAY_PICTURES',
                'type' => 'pictures',
                'name' => 'pictures',
                'default' => '0',
            ],
            [
                'title' => 'ACYM_CLICKABLE_TITLE',
                'type' => 'boolean',
                'name' => 'link',
                'default' => true,
            ],
            [
                'title' => 'ACYM_TRUNCATE',
                'type' => 'intextfield',
                'isNumber' => 1,
                'name' => 'wrap',
                'text' => 'ACYM_TRUNCATE_AFTER',
                'default' => 0,
            ],
        ];

        $this->autoContentOptions($displayOptions);

        $this->autoCampaignOptions($displayOptions);

        echo $this->pluginHelper->displayOptions($displayOptions, $this->name, 'simple', $this->defaultValues);
    }

    public function replaceContent(&$email)
    {
        $this->replaceMultiple($email);
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, $this->name);
        if (empty($tags)) return $this->generateCampaignResult;

        $this->tags = [];

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $parameter->display = empty($parameter->display) ? [] : explode(',', $parameter->display);

            // get RSS parsed object
            if (!empty($parameter->url) && !preg_match('/^http/i', $parameter->url)) $parameter->url = 'https://'.$parameter->url;
            $parameter->url = str_replace('&amp;', '&', $parameter->url);

            $rssDoc = simplexml_load_file($parameter->url);
            if ($rssDoc === false) {
                $rssDoc = simplexml_load_string(acym_fileGetContent($parameter->url));
            }

            if ($rssDoc === false) {
                if (acym_isAdmin()) acym_enqueueMessage(acym_translation('ACYM_RSS_LOAD_ERROR'), 'error');
                $this->generateCampaignResult->status = false;
                $this->generateCampaignResult->message = acym_translation('ACYM_RSS_LOAD_ERROR');

                return $this->generateCampaignResult;
            }

            $maxArticle = empty($parameter->max) ? 20 : $parameter->max;

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
            }

            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_replaceDate($parameter->min_publish);
            }

            $resultfeeds = [];
            foreach ($rssDoc->channel->item as $oneFeed) {
                if (count($resultfeeds) >= $maxArticle) break;

                if (!empty($oneFeed->pubDate)) {
                    $date = str_replace('&apos;', "'", $oneFeed->pubDate->__toString());
                    $date = strtotime($date);

                    if (!empty($parameter->min_publish) && $date < $parameter->min_publish) {
                        break;
                    }

                    if (!empty($lastGenerated) && $date < $lastGenerated) {
                        break;
                    }
                }

                $resultfeeds[] = $this->getItemView($oneFeed, $parameter);
            }

            if (!empty($parameter->min) && count($resultfeeds) < $parameter->min) {
                //We won't generate the Newsletter
                $this->generateCampaignResult->status = false;
                $this->generateCampaignResult->message = acym_translationSprintf(
                    'ACYM_GENERATE_CAMPAIGN_NOT_ENOUGH_CONTENT',
                    $this->pluginDescription->name,
                    count($resultfeeds),
                    $parameter->min
                );
            }

            $result = $this->pluginHelper->getFormattedResult($resultfeeds, $parameter);

            // Make sure there are no internal links
            $baseURL = 'https://'.parse_url($parameter->url, PHP_URL_HOST);
            $result = str_replace(['href="/', 'src="/'], ['href="'.$baseURL.'/', 'src="'.$baseURL.'/'], $result);

            $this->tags[$oneTag] = $result;
        }

        return $this->generateCampaignResult;
    }

    public function getItemView($oneFeed, $parameter)
    {
        $title = '';
        $afterTitle = '';
        $contentText = '';
        $link = '';
        $imagePath = '';
        $afterArticle = '';
        $customFields = [];

        $varFields['{title}'] = $oneFeed->title->__toString();
        if (in_array('title', $parameter->display)) $title = $varFields['{title}'];
        $varFields['{link}'] = strlen($oneFeed->link->__toString()) > 0 ? trim($oneFeed->link->__toString()) : '';
        if (!empty($parameter->link) && strlen($oneFeed->link->__toString()) > 0) {
            $link = $varFields['{link}'];
        }

        $varFields['{enclosure}'] = '';
        if ($oneFeed->enclosure) {
            $url = $oneFeed->enclosure['url']->__toString();
            $extension = acym_fileGetExt($url);
            if (in_array($extension, ['jpg', 'gif', 'png', 'jpeg'])) {
                $imagePath = $url;
            }
            $varFields['{enclosure}'] = $imagePath;

            if (!in_array('enclosure', $parameter->display)) $imagePath = '';
        }

        $varFields['{desc}'] = $oneFeed->description ? $oneFeed->description->__toString() : '';
        if (in_array('desc', $parameter->display) && $oneFeed->description) {
            $contentText = $varFields['{desc}'];
        }

        $varFields = array_merge(
            $varFields,
            [
                '{picthtml}' => '<img alt="" src="'.$imagePath.'">',
                '{authors}' => '',
                '{date}' => '',
            ]
        );

        $dcTags = $oneFeed->children('http://purl.org/dc/elements/1.1/');
        $authors = $oneFeed->children('author');
        if (strlen($authors->__toString()) == 0) {
            $authors = $dcTags->creator;
        } else {
            $authors = $oneFeed->creator;
        }
        if (strlen($authors->__toString()) > 0) {
            $textAuthors = [];
            foreach ($authors as $oneAuthor) {
                $textAuthors[] = $oneAuthor->__toString();
            }
            $varFields['{authors}'] = $textAuthors;
        }
        if (in_array('author', $parameter->display)) {

            $customFields[] = [
                $varFields['{authors}'],
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        if ($oneFeed->pubDate) {
            $date = str_replace('&apos;', "'", $oneFeed->pubDate->__toString());
            $date = acym_date(strtotime($date), 'ACYM_DATE_FORMAT_LC1');
            $varFields['{date}'] = $date;
        }
        if (in_array('date', $parameter->display) && $oneFeed->pubDate) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_PUBLISHING_DATE'),
            ];
        }

        $format = new stdClass();
        $format->tag = $parameter;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $parameter, $varFields);
    }
}
