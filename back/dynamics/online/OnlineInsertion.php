<?php

trait OnlineInsertion
{
    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        $others = [];
        $others['readonline'] = [
            'default' => acym_translation('ACYM_VIEW_ONLINE', true),
            'desc' => acym_translation('ACYM_VIEW_ONLINE_DESC'),
        ];
        if (ACYM_CMS == 'joomla') {
            $profilePage = acym_getPageLink('view=frontusers&layout=profile');
            $others['modify_profile'] = [
                'default' => acym_translation('ACYM_MODIFY_MY_PROFILE', true),
                'desc' => acym_translation('ACYM_MODIFY_PROFILE_DESC'),
                'disabled' => empty($profilePage),
                'tooltip' => acym_translation('ACYM_NO_PROFILE_MENU'),
            ];
        }

        ?>
		<script type="text/javascript">
            let selectedOnlineDText = '';

            function changeOnlineTag(tagName) {
                selectedOnlineDText = tagName;
                let defaultText = [];
                <?php
                foreach ($others as $tagname => $tag) {
                    echo 'defaultText["'.$tagname.'"] = "'.$tag['default'].'";';
                }
                ?>
                jQuery('#tr_' + tagName).addClass('selected_row');
                document.getElementById('acym__popup__online__tagtext').value = defaultText[tagName];

                <?php if (ACYM_CMS == 'joomla') { ?>
                if (selectedOnlineDText === 'readonline') {
                    jQuery('#acym__popup__online__theme__option').removeClass('is-hidden');
                } else {
                    jQuery('#acym__popup__online__theme__option').addClass('is-hidden');
                }
                <?php } ?>

                setOnlineTag();
            }

            function setOnlineTag() {
                // The value of the hidden input for the switch is changed after the onchange event is called...
                setTimeout(function () {
                    let themeOption = '';
                    if (selectedOnlineDText === 'readonline') {
                        let themeInput = document.querySelector('input[name="acym__popup__online__theme"]');
                        themeOption = '|theme:' + (themeInput && themeInput.value === '1' ? '1' : '0');
                    }
                    let tag = '{' + selectedOnlineDText + themeOption + '}' + document.getElementById('acym__popup__online__tagtext').value + '{/' + selectedOnlineDText + '}';
                    setTag(tag, jQuery('#tr_' + selectedOnlineDText));
                }, 50);
            }
		</script>

		<div class="acym__popup__listing text-center grid-x">
			<div class="grid-x medium-12 cell acym__row__no-listing text-left">
				<div class="grid-x cell">
					<label class="small-3" style="line-height: 40px;" for="acym__popup__online__tagtext"><?php echo acym_translation('ACYM_TEXT'); ?>: </label>
					<input class="small-9" type="text" name="tagtext" id="acym__popup__online__tagtext" onchange="setOnlineTag();">
				</div>
				<div class="grid-x cell margin-top-1 margin-bottom-1 is-hidden" id="acym__popup__online__theme__option">
                    <?php
                    echo acym_switch(
                        'acym__popup__online__theme',
                        false,
                        acym_translation('ACYM_OPEN_IN_SITE'),
                        ['onchange' => 'setOnlineTag();']
                    );
                    ?>
				</div>
				<div class="medium-auto"></div>
			</div>

            <?php
            foreach ($others as $tagname => $tag) {
                if (empty($tag['disabled'])) {
                    $onclick = 'onclick="changeOnlineTag(\''.$tagname.'\');"';
                    $class = '';
                } else {
                    $onclick = '';
                    $class = 'acym__listing__row__popup--disabled';
                }

                $rowHtml = '<div class="grid-x small-12 cell acym__row__no-listing acym__listing__row__popup text-left '.$class.'" '.$onclick.' id="tr_'.$tagname.'" >';
                $rowHtml .= '<div class="cell small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['desc'].'</div>';
                $rowHtml .= '</div>';

                if (!empty($tag['disabled'])) {
                    $rowHtml = acym_tooltip($rowHtml, $tag['tooltip'], 'cell');
                }

                echo $rowHtml;
            }
            ?>
		</div>

        <?php
    }

    public function replaceContent(&$email, $send = true)
    {
        if (empty($email->body)) return;

        // Parenthesis like (?:xxxx) are not stored in $results, so only (.*) is taken into account, in $results[1]
        $match = '#(?:{|%7B)(modify_profile|readonline(?:\|[^}]+)?)(?:}|%7D)(.*)(?:{|%7B)/(readonline|modify_profile)(?:}|%7D)#Uis';
        $results = [];
        $found = preg_match_all($match, $email->body, $results);

        if (!$found) return;

        $tags = [];
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


            // If there is nothing in $results[1] that means it's already a link
            if (empty($results[2][$i])) {
                $tags[$oneTag] = $link;
            } else {
                $tags[$oneTag] = '<a style="text-decoration:none;" href="'.$link.'" target="_blank"><span class="acym_online acym_link">'.$results[2][$i].'</span></a>';
            }
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }
}
