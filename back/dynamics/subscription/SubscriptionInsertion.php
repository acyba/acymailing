<?php

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\ListClass;

trait SubscriptionInsertion
{
    //Set this variable to true once the list unsubscribe is added so we don't add it twice
    private $addedListUnsubscribe = [];
    //Keep all lists and IDs for users so we don't do the query twice
    private $lists = [];
    //Keep all listsowner information so we don't do the query again and again
    private $listsowner = [];
    //Keep the list information there
    private $listsinfo = [];
    //Used to know if we should add a List-Unsubscribe header
    private $unsubscribeLink = [];
    // Used for list-unsubscribe to store the mail's list ids
    private $mailLists = [];
    private $userClass = null;

    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        $others = [];
        $others['unsubscribe'] = ['name' => acym_translation('ACYM_UNSUBSCRIBE_LINK'), 'default' => 'ACYM_UNSUBSCRIBE'];
        $others['confirm'] = ['name' => acym_translation('ACYM_CONFIRM_SUBSCRIPTION_LINK'), 'default' => 'ACYM_CONFIRM_SUBSCRIPTION'];
        $others['subscribe'] = ['name' => acym_translation('ACYM_SUBSCRIBE_LINK'), 'default' => 'ACYM_SUBSCRIBE'];

        ?>
        <script type="text/javascript">
            var openedLists = false;
            var selectedSubscriptionDText = '';

            function changeSubscriptionTag(tagName) {
                selectedSubscriptionDText = tagName;
                let defaultText = [];
                <?php
                foreach ($others as $tagname => $tag) {
                    echo 'defaultText["'.$tagname.'"] = "'.acym_translation($tag['default'], true).'";';
                }
                ?>
                jQuery('.acym__subscription__subscription').removeClass('selected_row');
                jQuery('#tr_' + tagName).addClass('selected_row');
                jQuery('#acym__popup__subscription__tagtext').val(defaultText[tagName]);
                setSubscriptionTag();
            }

            function setSubscriptionTag() {
                var tag = '{' + selectedSubscriptionDText;
                var lists = jQuery('#acym__popup__subscription__listids');

                if ('subscribe' === selectedSubscriptionDText) {
                    tag += '|lists:' + lists.html();
                } else if (openedLists) {
                    jQuery('#acym__popup__plugin__subscription__lists__modal').slideUp();
                    jQuery('#select_lists_zone').hide();
                    openedLists = false;
                }

                tag += '}' + jQuery('#acym__popup__subscription__tagtext').val() + '{/' + selectedSubscriptionDText + '}';
                setTag(tag, jQuery('#tr_' + selectedSubscriptionDText));
            }

            function displayLists() {
                if (openedLists) return;
                openedLists = true;

                jQuery.acymModal();
                jQuery('#acym__popup__plugin__subscription__lists__modal').slideDown();
                jQuery('#select_lists_zone').toggle();
                jQuery('#acym__popup__subscription__change').on('change', function () {
                    var lists = JSON.parse(jQuery('#acym__modal__lists-selected').val());
                    jQuery('#acym__popup__subscription__listids').html(lists.join());
                    changeSubscriptionTag('subscribe');
                });
            }
        </script>
        <?php

        //Add an area where the user will be able to select another text to add
        $text = '<div class="acym__popup__listing text-center grid-x">
                    <h1 class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_SUBSCRIPTION').'</h1>
                    <div class="grid-x medium-12 cell acym__row__no-listing text-left acym_vcenter">
                        <div class="grid-x cell medium-5 small-12 acym__listing__title acym__listing__title__dynamics acym__subscription__subscription acym_vcenter">
                            <label class="small-3 margin-bottom-0" for="acym__popup__subscription__tagtext">'.acym_translation('ACYM_TEXT').': </label>
                            <input class="small-9" type="text" name="tagtext" id="acym__popup__subscription__tagtext" onchange="setSubscriptionTag();">
                        </div>
                        <div class="medium-1"></div>
                        <div style="display: none;" id="select_lists_zone" class="grid-x cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">
                            <p class="shrink" id="acym__popup__subscription__text__list">'.acym_translation('ACYM_LISTS_SELECTED').'</p>
                            <p class="shrink" id="acym__popup__subscription__listids"></p>
                        </div>
                    </div>';
        $text .= '
					<div class="cell grid-x">';

        foreach ($others as $tagname => $tag) {
            $onclick = "changeSubscriptionTag('".$tagname."');";
            if ($tagname == 'subscribe') {
                $onclick .= 'displayLists();return false;';
            }
            $text .= '<div class="grid-x small-12 cell acym__row__no-listing acym__listing__row__popup text-left"  onclick="'.$onclick.'" id="tr_'.$tagname.'" >';
            $text .= '<div class="cell small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['name'].'</div>';
            $text .= '</div>';
        }
        $text .= '</div>
					<div class="medium-1"></div>
                    <div class="medium-10 text-left">';
        $text .= acym_modalPaginationLists(
            'acym__popup__subscription__change',
            '',
            false,
            'style="display: none;" id="acym__popup__plugin__subscription__lists__modal"'
        );
        $text .= '  </div>
                    <div class="medium-1"></div>
				</div>';

        // List tags
        $others = [];
        $others['name'] = acym_translation('ACYM_LIST_NAME');
        $others['description'] = acym_translation('ACYM_LIST_DESCRIPTION');
        $others['names'] = acym_translation('ACYM_LIST_NAMES');
        $others['descriptions'] = acym_translation('ACYM_LIST_DESCRIPTIONS');
        $others['id'] = acym_translation('ACYM_LIST_ID', true);

        $text .= '<div class="acym__popup__listing text-center grid-x">
					<h1 class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_LIST').'</h1>
					<div class="cell grid-x">';

        foreach ($others as $tagname => $tag) {
            $text .= '<div class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeSubscriptionTag(\'list\');setTag(\'{list:'.$tagname.'}\', jQuery(this));" id="tr_'.$tagname.'">
                        <div class="cell medium-12 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag.'</div>
                      </div>';
        }

        $text .= '</div></div>';

        // Newsletter tags
        $text .= '<div class="acym__popup__listing text-center grid-x">
					<span class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_CAMPAIGN').'</span>
					<div class="cell grid-x">';
        $othersMail = ['campaignid', 'subject'];

        foreach ($othersMail as $tag) {
            $text .= '<div class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeSubscriptionTag(\'mail\');setTag(\'{mail:'.$tag.'}\', jQuery(this));" id="tr_'.$tag.'">
                        <div class="cell medium-12 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag.'</div>
                      </div>';
        }
        $text .= '</div></div>';

        // Smart newsletter tags
        $text .= '<div class="acym__popup__listing text-center grid-x">
					<span class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_AUTO').' '.acym_translation('ACYM_CAMPAIGNS').'</span>
					<div class="cell grid-x">';
        $autoMail = ['number_generated' => ['name' => acym_translation('ACYM_ISSUE_NB'), 'default' => '#1']];

        foreach ($autoMail as $tag => $oneTag) {
            $tagInserted = $tag;
            if (!empty($oneTag['default'])) $tagInserted = $tag.'|default:'.$oneTag['default'];
            $text .= '<div class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeSubscriptionTag(\'automail\');setTag(\'{automail:'.$tagInserted.'}\', jQuery(this));" id="tr_'.$tag.'">
                        <div class="cell medium-12 small-12 acym__listing__title acym__listing__title__dynamics">'.$oneTag['name'].'</div>
                      </div>';
        }
        $text .= '</div></div>';

        echo $text;
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $this->_replacelisttags($email, $user, $send);

        // Check if we should add the List-Unsubscribe header
        if ($this->config->get('unsubscribe_header', 1) == 0) return;
        if (empty($user->id) || !empty($this->addedListUnsubscribe[$email->id][$user->id])) return;
        if (empty($this->unsubscribeLink[$email->id]) || !method_exists($email, 'addCustomHeader')) return;


        $this->addedListUnsubscribe[$email->id][$user->id] = true;

        // Prepare the mailto parameters for the header
        $mailto = '';
        if ($this->config->get('auto_bounce', 0)) {
            $mailto = $this->config->get('bounce_email');
        }
        if (empty($mailto)) {
            $mailto = empty($email->replyemail) ? $this->config->get('replyto_email') : $email->replyemail;
        }
        // No bounce address, no reply-to address on the email or the configuration
        if (empty($mailto)) return;

        $body = 'Please%20unsubscribe%20user%20ID%20'.$user->id;

        if (!isset($this->mailLists[$email->id])) {
            $mailClass = new MailClass();
            $lists = $mailClass->getAllListsByMailId($email->id);
            $this->mailLists[$email->id] = empty($lists) ? null : array_keys($lists);
        }

        // Make sure we unsubscribe from the correct lists and not all the lists
        if (!empty($this->mailLists[$email->id])) {
            $userClass = $this->getUserClass();
            $userLists = $userClass->getSubscriptionStatus($user->id, [], 1);
            if (!empty($userLists)) {
                $commonLists = array_intersect($this->mailLists[$email->id], array_keys($userLists));

                if (!empty($commonLists)) {
                    $body .= '%20from%20list(s)%20'.implode(',', $commonLists).'.';
                }
            }
        }

        $email->addCustomHeader('List-Unsubscribe', '<mailto:'.$mailto.'?subject=unsubscribe_user_'.$user->id.'&body='.$body.'>');
    }

    public function replaceContent(&$email, $send = true)
    {
        $this->_replaceSubscriptionTags($email);
        $this->_replacemailtags($email);
        $this->replaceAutomailTags($email);
    }

    private function _replacemailtags(&$email)
    {
        $result = $this->pluginHelper->extractTags($email, 'mail');
        $tags = [];

        foreach ($result as $key => $oneTag) {
            if (isset($tags[$key])) {
                continue;
            }

            $field = $oneTag->id;
            if (!empty($email) && !empty($email->$field)) {
                $text = $email->$field;
                $this->pluginHelper->formatString($text, $oneTag);
                $tags[$key] = $text;
            } elseif (substr($field, 0, 8) == 'campaign') {
                $this->getCampaignTags($email, $tags, $oneTag, $key);
            } else {
                $tags[$key] = $oneTag->default;
            }
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function getCampaignTags(&$email, &$tags, $oneTag, $key)
    {
        $campaignClass = new CampaignClass();
        $campaignFromMail = $campaignClass->getOneCampaignByMailId($email->id);
        $campaignField = substr($oneTag->id, 8);
        if (!empty($campaignFromMail) && !empty($campaignFromMail->$campaignField)) {
            $text = $campaignFromMail->$campaignField;
            $this->pluginHelper->formatString($text, $oneTag);
            $tags[$key] = $text;
        } else {
            $tags[$key] = $oneTag->default;
        }
    }

    // Available tags: {automail:number_generated}
    private function replaceAutomailTags(&$email)
    {
        $result = $this->pluginHelper->extractTags($email, 'automail');
        $tags = [];

        foreach ($result as $key => $oneTag) {
            if (isset($tags[$key])) {
                continue;
            }

            $field = $oneTag->id;

            $campaignClass = new CampaignClass();
            $autoCampaignFromMail = $campaignClass->getAutoCampaignFromGeneratedMailId($email->id);

            if (!empty($autoCampaignFromMail) && !empty($autoCampaignFromMail->sending_params[$field])) {
                $text = $autoCampaignFromMail->sending_params[$field];
                $this->pluginHelper->formatString($text, $oneTag);
                $tags[$key] = $text;
            } else {
                $tags[$key] = $oneTag->default;
            }
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    //Available tags: {list:name} , {list:count} , {list:count|listid:0} (to count all users), {list:members}, {list:owner}
    private function _replacelisttags(&$email, &$user, $send)
    {
        $tags = $this->pluginHelper->extractTags($email, 'list');
        if (empty($tags)) {
            return;
        }

        $replaceTags = [];
        foreach ($tags as $oneTag => $parameter) {
            $method = 'list'.trim(strtolower($parameter->id));

            if (method_exists($this, $method)) {
                $replaceTags[$oneTag] = $this->$method($email, $user, $parameter);
            } else {
                $replaceTags[$oneTag] = 'Method not found: '.$method;
            }
        }

        $this->pluginHelper->replaceTags($email, $replaceTags, true);
    }

    private function _getAttachedListid($email, $subid)
    {
        $mailid = $email->id;
        $type = strtolower($email->type);
        // 'standard','welcome','unsubscribe'

        if (isset($this->lists[$mailid][$subid])) {
            return $this->lists[$mailid][$subid];
        }

        $mailClass = new MailClass();
        $mailLists = array_keys($mailClass->getAllListsByMailId($mailid));
        $userLists = [];

        if (!empty($subid)) {
            $userClass = $this->getUserClass();
            $userLists = $userClass->getUserSubscriptionById($subid, 'id', false, false, false, true);

            $listid = null;
            foreach ($userLists as $id => $oneList) {
                if ($oneList->status == 1 && in_array($id, $mailLists)) {
                    $this->lists[$mailid][$subid] = $id;

                    return $this->lists[$mailid][$subid];
                }
            }

            if (!empty($listid)) {
                $this->lists[$mailid][$subid] = $listid;

                return $listid;
            }
        }

        //We could not find a list id there... maybe there is no subscription in which case we take the first one...
        if (!empty($mailLists)) {
            $this->lists[$mailid][$subid] = array_shift($mailLists);

            return $this->lists[$mailid][$subid];
        }

        if ($type == $mailClass::TYPE_WELCOME && !empty($subid)) {
            //Last list the user subscribed to...
            $listid = acym_loadResult(
                'SELECT list.id 
				FROM #__acym_list AS list 
				JOIN #__acym_user_has_list AS userlist ON list.id = userlist.list_id 
				WHERE list.welcome_id = '.intval($mailid).' AND userlist.user_id = '.intval($subid).' 
				ORDER BY userlist.subscription_date DESC'
            );
            if (!empty($listid)) {
                $this->lists[$mailid][$subid] = $listid;

                return $listid;
            }
        }

        if ($type == $mailClass::TYPE_UNSUBSCRIBE && !empty($subid)) {
            //Last list the user unsubscribed from...
            $listid = acym_loadResult(
                'SELECT list.id 
				FROM #__acym_list AS list 
				JOIN #__acym_user_has_list AS userlist ON list.id = userlist.list_id 
				WHERE list.unsubscribe_id = '.intval($mailid).' AND userlist.user_id = '.intval($subid).' 
				ORDER BY userlist.unsubscribe_date DESC'
            );
            if (!empty($listid)) {
                $this->lists[$mailid][$subid] = $listid;

                return $listid;
            }
        }

        //Still no list? well, lets load the first list the user is subscribed to then
        if (!empty($userLists)) {
            $listIds = array_keys($userLists);
            $this->lists[$mailid][$subid] = array_shift($listIds);

            return $this->lists[$mailid][$subid];
        }

        return 0;
    }

    private function listnames(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) return '';

        $userClass = $this->getUserClass();
        $usersubscription = $userClass->getUserSubscriptionById($user->id, 'id', false, true);
        $lists = [];
        if (!empty($usersubscription)) {
            foreach ($usersubscription as $onesub) {
                if ($onesub->status < 1 || empty($onesub->active)) {
                    continue;
                }
                $lists[] = (!empty($onesub->display_name) ? $onesub->display_name : $onesub->name);
            }
        }

        return implode(isset($parameter->separator) ? $parameter->separator : ', ', $lists);
    }

    /**
     * Replace data about the list owner
     * Use the tag:  {list:owner|field:name} or {list:owner|field:username} or {list:owner|field:email}
     */
    private function listowner(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) {
            return '';
        }
        $listid = $this->_getAttachedListid($email, $user->id);
        if (empty($listid)) {
            return "";
        }

        if (!isset($this->listsowner[$listid])) {
            $this->listsowner[$listid] = acym_loadObject(
                'SELECT user.* FROM #__acym_list AS list JOIN '.$this->cmsUserVars->table.' AS user ON user.'.$this->cmsUserVars->id.' = list.cms_user_id WHERE list.id = '.intval(
                    $listid
                )
            );
        }

        if (!in_array($parameter->field, [$this->cmsUserVars->username, $this->cmsUserVars->name, $this->cmsUserVars->email])) {
            return 'Field not found : '.$parameter->field;
        }

        return @$this->listsowner[$listid]->{$this->cmsUserVars->{$parameter->field}};
    }

    private function listname(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) {
            return '';
        }
        $listid = $this->_getAttachedListid($email, $user->id);
        if (empty($listid)) {
            return '';
        }

        $this->_loadlist($listid);

        return !empty($this->listsinfo[$listid]->display_name) ? $this->listsinfo[$listid]->display_name : @$this->listsinfo[$listid]->name;
    }

    private function listdescription(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) {
            return '';
        }
        if (!empty($parameter->listid)) $listid = $parameter->listid;
        if (empty($listid)) $listid = $this->_getAttachedListid($email, $user->id);
        if (empty($listid)) {
            return '';
        }

        $this->_loadlist($listid);

        return @$this->listsinfo[$listid]->description;
    }

    private function _loadlist($listid)
    {
        if (isset($this->listsinfo[$listid])) {
            return;
        }

        $listClass = new ListClass();
        $this->listsinfo[$listid] = $listClass->getOneById(intval($listid));
    }

    private function listdescriptions(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) return '';

        $userClass = $this->getUserClass();
        $usersubscription = $userClass->getUserSubscriptionById($user->id);
        $listids = [];
        if (!empty($parameter->listids)) $listids = explode(',', $parameter->listids);
        $lists = [];
        if (!empty($usersubscription)) {
            foreach ($usersubscription as $onesub) {
                if (empty($onesub->description) || $onesub->status < 1 || empty($onesub->active) || (!empty($listids) && !in_array($onesub->id, $listids))) {
                    continue;
                }
                $lists[] = $onesub->description;
            }
        }

        return implode(isset($parameter->separator) ? $parameter->separator : ', ', $lists);
    }

    private function listid(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) {
            return '';
        }
        $listid = $this->_getAttachedListid($email, $user->id);
        if (empty($listid)) {
            return '';
        }

        return $listid;
    }

    private function _replaceSubscriptionTags(&$email)
    {
        $match = '#(?:{|%7B)(confirm|unsubscribe|subscribe(?:\|[^}]+)*)(?:}|%7D)(.*)(?:{|%7B)/(confirm|unsubscribe|subscribe)(?:}|%7D)#Uis';
        $variables = ['subject', 'body'];
        $found = false;
        $results = [];
        foreach ($variables as $var) {
            if (empty($email->$var)) continue;

            $found = preg_match_all($match, $email->$var, $results[$var]) || $found;
            //we unset the results so that we won't handle it later... it will save some memory and processing
            if (empty($results[$var][0])) unset($results[$var]);
        }

        //If we didn't find anything...
        if (!$found) return;

        $tags = [];
        $this->addedListUnsubscribe[$email->id] = [];
        foreach ($results as $var => $allresults) {
            foreach ($allresults[0] as $i => $oneTag) {
                //Don't need to process twice a tag we already have!
                if (isset($tags[$oneTag])) continue;

                $tags[$oneTag] = $this->_replaceSubscriptionTag($allresults, $i, $email);
            }
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function _replaceSubscriptionTag(&$allresults, $i, &$email)
    {
        $parameters = $this->pluginHelper->extractTag($allresults[1][$i]);

        $lang = $this->getLanguage($email->links_language);

        if ($parameters->id === 'confirm') {
            // subscription confirmation link
            $myLink = acym_frontendLink('frontusers&task=confirm&userId={subscriber:id}&userKey={subscriber:key|urlencode}'.$lang);
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a target="_blank" href="'.$myLink.'"><span class="acym_confirm acym_link">'.$allresults[2][$i].'</span></a>';
        } elseif ($parameters->id === 'subscribe') {
            // direct subscription link
            if (empty($parameters->lists)) {
                return acym_translation('ACYM_EXPORT_SELECT_LIST');
            }
            $lists = explode(',', $parameters->lists);
            acym_arrayToInteger($lists);
            $captchaKey = $this->config->get('captcha', 'none') !== 'none' ? '&seckey='.$this->config->get('security_key', '') : '';
            $myLink = acym_frontendLink(
                'frontusers&task=subscribe&hiddenlists='.implode(',', $lists).'&userId={subscriber:id}&userKey={subscriber:key|urlencode}'.$lang.$captchaKey
            );
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a style="text-decoration:none;" target="_blank" href="'.$myLink.'"><span class="acym_subscribe acym_link">'.$allresults[2][$i].'</span></a>';
        } else {
            // unsubscribe link
            $this->unsubscribeLink[$email->id] = true;

            $myLink = 'frontusers&task=unsubscribe&userId={subscriber:id}&userKey={subscriber:key|urlencode}'.$lang.'&mail_id='.$email->id;
            if ($this->config->get('unsubpage_header') != 1) {
                $myLink .= '&'.acym_noTemplate();
            }
            $myLink = acym_frontendLink($myLink);

            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a style="text-decoration:none;" target="_blank" href="'.$myLink.'"><span class="acym_unsubscribe acym_link">'.$allresults[2][$i].'</span></a>';
        }
    }

    private function getUserClass()
    {
        if ($this->userClass === null) {
            $this->userClass = new UserClass();
        }

        return $this->userClass;
    }
}
