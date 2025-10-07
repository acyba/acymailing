<?php

use AcyMailing\Classes\FieldClass;

trait OnlineInsertion
{
    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        $links = [
            'readonline' => [
                'default' => acym_translation('ACYM_VIEW_ONLINE', true),
                'desc' => acym_translation('ACYM_VIEW_ONLINE_DESC'),
            ],
            'modify_profile' => [
                'default' => acym_translation('ACYM_MODIFY_MY_PROFILE', true),
                'desc' => acym_translation('ACYM_MODIFY_PROFILE_DESC'),
                'disabled' => (ACYM_CMS == 'joomla' && empty(acym_getPageLink('view=frontusers&layout=profile'))),
                'tooltip' => acym_translation('ACYM_NO_PROFILE_MENU'),
            ],
        ];

        $information = [];
        if (ACYM_CMS === 'joomla') {
            $baseUrlValue = ACYM_J50 ? \Joomla\CMS\Uri\Uri::root() : JURI::root();
            $information = [
                'site_name' => [
                    'label' => acym_translation('ACYM_SITE_NAME'),
                    'value' => acym_getCMSConfig('sitename'),
                ],
                'base_url' => [
                    'label' => ucfirst(acym_translationSprintf('ACYM_X_URL', ACYM_CMS)),
                    'value' => $baseUrlValue,
                ],
            ];
        } elseif (ACYM_CMS === 'wordpress') {
            $information = [
                'site_name' => [
                    'label' => acym_translation('ACYM_SITE_NAME'),
                    'value' => acym_getCMSConfig('sitename'),
                ],
                'wp_address' => [
                    'label' => ucfirst(acym_translationSprintf('ACYM_X_ADDRESS', ACYM_CMS)),
                    'value' => acym_getCMSConfig('siteurl'),
                ],
                'site_address' => [
                    'label' => acym_translation('ACYM_SITE_URL'),
                    'value' => acym_getCMSConfig('home'),
                ],
                'tagline' => [
                    'label' => acym_translation('ACYM_TAGLINE'),
                    'value' => acym_getCMSConfig('blogdescription'),
                ],
                'site_icon' => [
                    'label' => acym_translation('ACYM_SITE_ICON'),
                    'value' => get_site_icon_url(),
                ],
                'admin_email' => [
                    'label' => acym_translation('ACYM_ADMIN_EMAIL'),
                    'value' => acym_getCMSConfig('admin_email'),
                ],
            ];
        }
        ?>
		<script type="text/javascript">
            let selectedOnlineDText = '';
            let selectedInfoKey = '';

            function changeOnlineTag(tagName, infoKey = '', disableInput = false) {
                selectedOnlineDText = tagName;
                selectedInfoKey = infoKey;
                const defaultText = [];
                <?php
                foreach ($links as $tagname => $tag) {
                    echo 'defaultText["'.$tagname.'"] = "'.$tag['default'].'";';
                }
                foreach ($information as $infoKey => $info) {
                    echo 'defaultText["info_'.$infoKey.'"] = \''.str_replace("'", "\\'", $info['value']).'\';';
                }
                ?>
                jQuery('.selected_row').removeClass('selected_row');
                jQuery('#tr_' + tagName).addClass('selected_row');

                if (infoKey === '') {
                    document.getElementById('acym__popup__online__tagtext').value = defaultText[tagName];
                } else {
                    document.getElementById('acym__popup__online__tagtext').value = '';
                    document.getElementById('acym__popup__online__tagtext').setAttribute('data-value', defaultText['info_' + infoKey]);
                }

                if (disableInput) {
                    document.getElementById('acym__popup__online__tagtext').setAttribute('readonly', 'readonly');
                } else {
                    document.getElementById('acym__popup__online__tagtext').removeAttribute('readonly');
                }

                setOnlineTag();
            }

            function getTagText() {
                const tagText = document.getElementById('acym__popup__online__tagtext');
                return tagText.value != '' ? tagText.value : tagText.getAttribute('data-value') || '';
            }

            function setOnlineTag() {
                setTimeout(function () {
                    let themeOption = '';
                    if (selectedOnlineDText === 'readonline') {
                        let themeInput = document.querySelector('input[name="acym__popup__online__theme"]');
                        themeOption = '|theme:' + (themeInput && themeInput.value === '1' ? '1' : '0');
                    }
                    let tag = '{'
                              + (selectedOnlineDText === 'info' ? 'info:' + selectedInfoKey : selectedOnlineDText)
                              + themeOption
                              + '}'
                              + getTagText()
                              + '{/'
                              + selectedOnlineDText
                              + '}';
                    setTag(tag, jQuery('#tr_' + selectedOnlineDText));
                }, 50);
            }
		</script>

        <?php
        include acym_getPartial('editor', 'website_content');
    }

    public function replaceContent(&$email, $send = true)
    {
        if (empty($email->body)) return;

        $tags = $this->replaceInformationTags($email);

        $match = '#(?:{|%7B)(modify_profile|readonline(?:\|[^}]+)?)(?:}|%7D)(.*)(?:{|%7B)/(readonline|modify_profile)(?:}|%7D)#Uis';
        $results = [];
        $found = preg_match_all($match, $email->body, $results);

        if (!$found && empty($tags)) {
            return;
        };

        foreach ($results[0] as $i => $oneTag) {
            if (isset($tags[$oneTag])) continue;

            if (ACYM_CMS == 'joomla' && strpos($oneTag, 'modify_profile') !== false) {
                $link = acym_getPageLink('view=frontusers&layout=profile');
                $link .= strpos($link, '?') ? '&' : '?';
                $link .= 'id={subscriber:id}&key={subscriber:key}';
                $link .= $this->getLanguage($email->links_language);
            } else {
                $link = 'archive&task=view&id='.$email->id.'&userid={subscriber:id}-{subscriber:key}';
                if (strpos($results[1][$i], 'theme:1') === false) {
                    $link .= '&'.acym_noTemplate();
                }
                if (!empty($email->key)) {
                    $link .= '&key='.$email->key;
                }
                $link .= $this->getLanguage($email->links_language);
                $link = acym_frontendLink($link);
            }

            if (empty($results[2][$i])) {
                $tags[$oneTag] = $link;
            } else {
                $tags[$oneTag] = '<a style="text-decoration:none;" href="'.$link.'" target="_blank"><span class="acym_online acym_link">'.$results[2][$i].'</span></a>';
            }
        }
        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function replaceInformationTags(&$email): array
    {
        $match = '#\{info:([a-z_]+)\}(.*?)\{\/info\}#is';
        $extractedTags = [];
        $found = preg_match_all($match, $email->body, $extractedTags);
        $tags = [];

        if (!$found) {
            return $tags;
        }

        foreach ($extractedTags[0] as $i => $fullMatch) {
            $content = $extractedTags[2][$i];
            $fieldValue = '';

            if (acym_isValidEmail($content)) {
                $fieldValue = '<a style="text-decoration:none;" href="mailto:'.$content.'"><span class="acym_online acym_link">'.$content.'</span></a>';
            } elseif (acym_isValidUrl($content)) {
                if (acym_isImageUrl($content)) {
                    $fieldValue = '<img src="'.$content.'" alt="Image" style="display: inline-block; max-width: 25px; max-height: 25px; vertical-align: middle;" />';
                } else {
                    $fieldValue = '<a style="text-decoration:none;" href="'.$content.'" target="_blank"><span class="acym_online acym_link">'.$content.'</span></a>';
                }
            } else {
                $fieldValue = $content;
            }
            $tags[$fullMatch] = $fieldValue;
        }

        return $tags;
    }
}
