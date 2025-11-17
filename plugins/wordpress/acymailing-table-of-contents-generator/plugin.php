<?php

use AcyMailing\Core\AcymPlugin;

class plgAcymTableofcontents extends AcymPlugin
{
    public $emailsWithNoAnchors = [];
    private $updateMail = [];
    private $level1Links = [];
    private $level2Links = [];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = 'Table of contents';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.svg';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->description = '- Insert a dynamic table of contents in your emails based on their contents';
    }

    public function getPossibleIntegrations(): ?object
    {
        return $this->pluginDescription;
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        $this->defaultValues = $defaultValues;

        $anchorOptions = [
            'existing' => 'ACYM_EXISTING_ANCHORS',
            'h1' => 'H1',
            'h2' => 'H2',
            'h3' => 'H3',
            'h4' => 'H4',
            'h5' => 'H5',
            'h6' => 'H6',
            'none' => 'ACYM_DISABLED',
        ];

        $displayOptions = [
            [
                'title' => acym_translationSprintf('ACYM_LEVEL_X', '1'),
                'type' => 'select',
                'name' => 'level1',
                'options' => $anchorOptions,
                'section' => 'ACYM_TABLE_OF_CONTENTS_SOURCE',
                'default' => 'existing',
            ],
            [
                'title' => acym_translationSprintf('ACYM_LEVEL_X', '2'),
                'type' => 'select',
                'name' => 'level2',
                'options' => $anchorOptions,
                'section' => 'ACYM_TABLE_OF_CONTENTS_SOURCE',
                'default' => 'none',
            ],
            [
                'title' => 'ACYM_TYPE',
                'type' => 'select',
                'name' => 'type',
                'options' => [
                    'br' => 'ACYM_NEW_LINE',
                    'li' => 'ACYM_BULLET_LIST',
                ],
                'default' => 'br',
            ],
        ];

        $this->pluginHelper->displayOptions($displayOptions, $this->name, 'simple', $this->defaultValues);
    }

    /**
     * We use this trigger to be sure all the dynamic content has been inserted before building the table of contents
     *
     * @param object $email
     * @param object $user
     * @param bool   $send
     */
    public function replaceContent(object &$email): void
    {
        if (isset($this->emailsWithNoAnchors[intval($email->id)])) return;

        $extractedTags = $this->pluginHelper->extractTags($email, $this->name);
        if (empty($extractedTags)) {
            $this->emailsWithNoAnchors[intval($email->id)] = true;

            return;
        }

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) continue;

            $tags[$i] = $this->generateTable($email, $oneTag);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function generateTable(&$email, $tag)
    {
        // 1 - Prepare types configuration
        if ($tag->type == 'br') {
            $tag->divider = '<br />';
            $tag->subdivider = '<br /> - ';
            $tag->before = '';
            $tag->after = '';
            $tag->subbefore = '<br /> - ';
            $tag->subafter = '';
        } elseif ($tag->type == 'li') {
            $tag->divider = '</li><li>';
            $tag->subdivider = '</li><li>';
            $tag->before = '<ul><li>';
            $tag->after = '</li></ul>';
            $tag->subbefore = '<ul><li>';
            $tag->subafter = '</li></ul>';
        }

        // 2 - Generate/get the anchors from the email's body
        $this->updateMail = [];
        $this->level1Links = [];
        $this->level2Links = [];
        $anchorLinks = $this->findLinks($tag, $email);
        if (!empty($tag->level2)) {
            $anchorsLevel2 = $this->findLinks($tag, $email, true);
            if (empty($this->level1Links)) {
                $this->level1Links = $this->level2Links;
                unset($this->level2Links);
            }
        }

        if (empty($this->level1Links)) return '';

        // 3 - Add the anchors to the email's body
        if (!empty($this->updateMail)) {
            $email->body = str_replace(array_keys($this->updateMail), $this->updateMail, $email->body);
        }

        // 4 - Prepare the output
        if (!empty($this->level2Links)) {
            foreach ($this->level1Links as $arrayPosition => $oneLink) {
                $subLinksForCurrentSection = [];
                $from = $anchorLinks['position'][$arrayPosition];
                $to = empty($anchorLinks['position'][$arrayPosition + 1]) ? 9999999999999 : $anchorLinks['position'][$arrayPosition + 1];
                foreach ($this->level2Links as $key => $oneSubLink) {
                    if ($anchorsLevel2['position'][$key] > $to) break;
                    if ($anchorsLevel2['position'][$key] > $from) $subLinksForCurrentSection[] = $oneSubLink;
                }

                // The current section has sub links, add them as level 2 anchors
                if (!empty($subLinksForCurrentSection)) {
                    $this->level1Links[$arrayPosition] = $this->level1Links[$arrayPosition].$tag->subbefore.implode($tag->subdivider, $subLinksForCurrentSection).$tag->subafter;
                }
            }
        }

        return '<div class="table_of_contents">'.$tag->before.implode($tag->divider, $this->level1Links).$tag->after.'</div>';
    }

    private function findLinks(&$tag, &$email, $sub = false)
    {
        $emailVariable = empty($email->previewBody) ? 'body' : 'previewBody';

        if ($sub) {
            $varType = 'level2';
            $varLink = &$this->level2Links;
        } else {
            $varType = 'level1';
            $varLink = &$this->level1Links;
        }

        if ($tag->$varType === 'none') return '';

        if ($tag->$varType === 'existing') {
            preg_match_all('#<a[^>]*name="([^">]*)"[^>]*>(?!</ *a>).*</ *a>#Uis', $email->$emailVariable, $anchorsDetected);
        } else {
            preg_match_all('#<'.$tag->$varType.'[^>]*>((?!</ *'.$tag->$varType.'>).*)</ *'.$tag->$varType.'>#Uis', $email->$emailVariable, $anchorsDetected);
        }

        if (empty($anchorsDetected)) return '';


        foreach ($anchorsDetected[0] as $i => $oneContent) {
            $anchorsDetected['position'][$i] = strpos($email->$emailVariable, $oneContent);
            $linkText = preg_replace('#<[^>]*>#Uis', '', $oneContent);
            if (empty($linkText)) continue;
            if ($tag->$varType === 'existing') {
                $varLink[$i] = '<a href="#'.$anchorsDetected[1][$i].'" class="oneitem">'.$linkText.'</a>';
            } else {
                // Prepare the new body content where the anchors have been added just before the focused elements (before the h1, h2, etc...)
                $varLink[$i] = '<a href="#'.$tag->$varType.$i.'" class="oneitem oneitem'.$tag->$varType.'">'.$linkText.'</a>';
                if (preg_match('#<a[^>]*>[^<]*'.preg_quote($oneContent, '#').'#Uis', $email->$emailVariable, $linkBefore)) {
                    $this->updateMail[$linkBefore[0]] = '<a name="'.$tag->$varType.$i.'"></a>'.$linkBefore[0];
                } else {
                    $this->updateMail[$oneContent] = '<a name="'.$tag->$varType.$i.'"></a>'.$oneContent;
                }
            }
        }

        return $anchorsDetected;
    }
}
