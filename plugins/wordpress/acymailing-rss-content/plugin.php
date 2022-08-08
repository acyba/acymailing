<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymRss extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = 'RSS and Atom feeds';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.svg';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->features = '["content"]';
        $this->pluginDescription->description = '- Insert content in your emails from an RSS link<br />- Insert content in your emails from an Atom feed';

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
                'name' => 'clickable',
                'default' => true,
            ],
            [
                'title' => 'ACYM_CLICKABLE_IMAGE',
                'type' => 'boolean',
                'name' => 'clickableimg',
                'default' => false,
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
            if (empty($rssDoc->channel)) {
                foreach ($rssDoc->entry as $oneFeed) {
                    if (count($resultfeeds) >= $maxArticle) break;

                    if (!empty($oneFeed->published)) {
                        $date = str_replace('&apos;', "'", $oneFeed->published->__toString());
                        $date = strtotime($date);

                        if (!empty($parameter->min_publish) && $date < $parameter->min_publish) {
                            break;
                        }

                        if (!empty($lastGenerated) && $date < $lastGenerated) {
                            break;
                        }
                    }

                    $resultfeeds[] = $this->getItemView($oneFeed, $parameter, 'ATOM');
                }
            } else {
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

                    $resultfeeds[] = $this->getItemView($oneFeed, $parameter, 'RSS');
                }
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

    public function getItemView($oneFeed, $parameter, $type)
    {
        $title = '';
        $afterTitle = '';
        $contentText = '';
        $link = '';
        $imagePath = '';
        $afterArticle = '';
        $customFields = [];

        $varFields['{title}'] = $oneFeed->title->__toString();
        if (in_array('title', $parameter->display)) {
            $title = $varFields['{title}'];
        }

        $varFields['{link}'] = '';
        $varFields['{enclosure}'] = '';
        $varFields['{picthtml}'] = '';

        if ($type === 'RSS') {
            if (strlen($oneFeed->link->__toString()) > 0) {
                $varFields['{link}'] = trim($oneFeed->link->__toString());
            }

            if (!empty($varFields['{link}'])) {
                $link = $varFields['{link}'];
            }

            if ($oneFeed->enclosure && !empty($oneFeed->enclosure['url'])) {
                $url = $oneFeed->enclosure['url']->__toString();
                $extension = acym_fileGetExt($url);
                if (in_array($extension, ['jpg', 'gif', 'png', 'jpeg'])) {
                    $imagePath = $url;
                }
            }
        } else {
            if (!empty($oneFeed->link->attributes()->href) && !empty($oneFeed->link->attributes()->href->__toString())) {
                $varFields['{link}'] = trim($oneFeed->link->attributes()->href->__toString());
            }
            if (!empty($varFields['{link}'])) {
                $link = $varFields['{link}'];
            }

            $namespaces = $oneFeed->getNamespaces(true);
            if (!empty($namespaces['media'])) {
                $media = $oneFeed->children($namespaces['media']);
                if (!empty($media) && $media->group && $media->group->thumbnail) {
                    $attributes = $media->group->thumbnail->attributes();
                    if (!empty($attributes) && $attributes->url) {
                        $imagePath = $media->group->thumbnail->attributes()->url->__toString();
                    }
                }
            }
        }
        if (!empty($imagePath)) {
            $varFields['{enclosure}'] = $imagePath;
            $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
            if (!in_array('enclosure', $parameter->display)) {
                $imagePath = '';
            }
        }

        if ($type === 'RSS') {
            $varFields['{desc}'] = $oneFeed->description ? $oneFeed->description->__toString() : '';
        } else {
            $varFields['{desc}'] = $oneFeed->summary ? $oneFeed->summary->__toString() : ($oneFeed->content ? $oneFeed->content->__toString() : '');
            if (empty($varFields['{desc}'])) {
                $namespaces = $oneFeed->getNamespaces(true);
                if (!empty($namespaces['media'])) {
                    $media = $oneFeed->children($namespaces['media']);
                    if (!empty($media) && $media->group && $media->group->description) {
                        $varFields['{desc}'] = $media->group->description->__toString();
                    }
                }
            }
        }

        if (in_array('desc', $parameter->display) && !empty($varFields['{desc}'])) {
            $contentText = $varFields['{desc}'];
        }

        $varFields['{authors}'] = '';
        $varFields['{date}'] = '';

        if ($type === 'RSS') {
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
        } else {
            if ($oneFeed->author && $oneFeed->author->name) {
                $varFields['{authors}'] = $oneFeed->author->name->__toString();
            }
        }

        if (in_array('author', $parameter->display) && !empty($varFields['{authors}'])) {
            $customFields[] = [
                $varFields['{authors}'],
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        $publishField = $type === 'RSS' ? 'pubDate' : 'published';
        if ($oneFeed->$publishField) {
            $date = str_replace('&apos;', "'", $oneFeed->$publishField->__toString());
            $date = acym_date(strtotime($date), 'ACYM_DATE_FORMAT_LC1');
            $varFields['{date}'] = $date;
        }

        if (in_array('date', $parameter->display) && !empty($varFields['{date}'])) {
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
