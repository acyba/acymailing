<?php

class plgAcymOnline extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_WEBSITE_LINKS');
    }

    public function dynamicText()
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        $others = [];
        $others['readonline'] = ['default' => acym_translation('ACYM_VIEW_ONLINE', true), 'desc' => acym_translation('ACYM_VIEW_ONLINE_DESC')];

        ?>
		<script language="javascript" type="text/javascript">
            <!--
            var selectedTag = '';

            function changeOnlineTag(tagName) {
                selectedTag = tagName;
                defaultText = [];
                <?php
                foreach ($others as $tagname => $tag) {
                    echo 'defaultText["'.$tagname.'"] = "'.$tag['default'].'";';
                }
                ?>
                jQuery('#tr_' + tagName).addClass('selected_row');
                document.getElementById('acym__popup__online__tagtext').value = defaultText[tagName];

                setOnlineTag();
            }

            function setOnlineTag() {
                var tag = '{' + selectedTag + '}' + document.getElementById('acym__popup__online__tagtext').value + '{/' + selectedTag + '}';
                setTag(tag, jQuery('#tr_' + selectedTag));
            }

            //-->
		</script>

		<div class="acym__popup__listing text-center grid-x">
			<div class="grid-x medium-12 cell acym__row__no-listing text-left">
				<div class="grid-x cell medium-5 small-12 acym__listing__title acym__listing__title__dynamics">
					<label class="small-3" style="line-height: 40px;" for="acym__popup__online__tagtext"><?php echo acym_translation('ACYM_TEXT'); ?>: </label>
					<input class="small-9" type="text" name="tagtext" id="acym__popup__online__tagtext" onchange="setOnlineTag();">
				</div>
				<div class="medium-auto"></div>
			</div>

            <?php
            foreach ($others as $tagname => $tag) {
                $onclick = 'changeOnlineTag(\''.$tagname.'\');';
                echo '<div class="grid-x small-12 cell acym__row__no-listing acym__listing__row__popup text-left"  onclick="'.$onclick.'" id="tr_'.$tagname.'" ><div class="cell small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['desc'].'</div></div>';
            }
            ?>
		</div>

        <?php
    }

    public function replaceContent(&$email, $send = true)
    {
        if (empty($email->body)) return;

        // Parenthesis like (?:xxxx) are not stored in $results, so only (.*) is taken into account, in $results[1]
        $match = '#(?:{|%7B)readonline(?:}|%7D)(.*)(?:{|%7B)/readonline(?:}|%7D)#Uis';
        $results = [];
        $found = preg_match_all($match, $email->body, $results);

        if (!$found) return;

        $tags = [];
        foreach ($results[0] as $i => $oneTag) {
            if (isset($tags[$oneTag])) continue;

            $link = 'archive&task=view&id='.$email->id.'&userid={subtag:id}-{subtag:key}&'.acym_noTemplate();
            $link .= $this->getLanguage($email->links_language);
            if (!empty($email->key)) $link .= '&key='.$email->key;

            $link = acym_frontendLink($link);

            // If there is nothing in $results[1] that means it's already a link
            if (empty($results[1][$i])) {
                $tags[$oneTag] = $link;
            } else {
                $tags[$oneTag] = '<a style="text-decoration:none;" href="'.$link.'" target="_blank"><span class="acym_online">'.$results[1][$i].'</span></a>';
            }
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }
}
