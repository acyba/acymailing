<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\AutomationClass;

class plgAcymSubscription extends acymPlugin
{
    const FOLLOWTRIGGER = 'user_subscribe';

    //Set this variable to true once the list unsubscribe is added so we don't add it twice
    var $addedListUnsubscribe = [];
    //Keep all lists and IDs for users so we don't do the query twice
    var $lists = [];
    //Keep all listsowner information so we don't do the query again and again
    var $listsowner = [];
    //Keep the list information there
    var $listsinfo = [];
    //Campaigns list
    var $campaigns = [];
    //Used to know if we should add a List-Unsubscribe header
    var $unsubscribeLink = [];
    // Used for list-unsubscribe to store the mail's list ids
    private $mailLists = [];
    private $userClass = null;

    public function __construct()
    {
        parent::__construct();

        global $acymCmsUserVars;
        $this->cmsUserVars = $acymCmsUserVars;

        $this->pluginDescription->name = acym_translation('ACYM_SUBSCRIPTION');
    }

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
		<script language="javascript" type="text/javascript">
            var openedLists = false;
            var selectedSubscriptionDText = '';

            function changeSubscriptionTag(tagName) {
                selectedSubscriptionDText = tagName;
                defaultText = [];
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
        $this->replacAutomailTags($email);
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
    private function replacAutomailTags(&$email)
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
                $lists[] = $onesub->name;
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

    private function _loadlist($listid)
    {
        if (isset($this->listsinfo[$listid])) {
            return;
        }

        $listClass = new ListClass();
        $this->listsinfo[$listid] = $listClass->getOneById(intval($listid));
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

        return @$this->listsinfo[$listid]->name;
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

        if ($parameters->id == 'confirm') {
            // subscription confirmation link
            $myLink = acym_frontendLink('frontusers&task=confirm&id={subscriber:id}&key={subscriber:key|urlencode}'.$lang);
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a target="_blank" href="'.$myLink.'"><span class="acym_confirm acym_link">'.$allresults[2][$i].'</span></a>';
        } elseif ($parameters->id == 'subscribe') {
            // direct subscription link
            if (empty($parameters->lists)) {
                return acym_translation('ACYM_EXPORT_SELECT_LIST');
            }
            $lists = explode(',', $parameters->lists);
            acym_arrayToInteger($lists);
            $captchaKey = $this->config->get('captcha', 'none') !== 'none' ? '&seckey='.$this->config->get('security_key', '') : '';
            $myLink = acym_frontendLink('frontusers&task=subscribe&hiddenlists='.implode(',', $lists).'&id={subscriber:id}&key={subscriber:key|urlencode}'.$lang.$captchaKey);
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a style="text-decoration:none;" target="_blank" href="'.$myLink.'"><span class="acym_subscribe acym_link">'.$allresults[2][$i].'</span></a>';
        } else {
            // unsubscribe link
            $this->unsubscribeLink[$email->id] = true;

            $myLink = 'frontusers&task=unsubscribe&id={subscriber:id}&key={subscriber:key|urlencode}'.$lang.'&mail_id='.$email->id;
            if ($this->config->get('unsubpage_header') != 1) $myLink .= '&'.acym_noTemplate();
            $myLink = acym_frontendLink($myLink);
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a style="text-decoration:none;" target="_blank" href="'.$myLink.'"><span class="acym_unsubscribe acym_link">'.$allresults[2][$i].'</span></a>';
        }
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $listClass = new ListClass();
        $list = [
            'type' => [
                'sub' => acym_translation('ACYM_SUBSCRIBED'),
                'unsub' => acym_translation('ACYM_UNSUBSCRIBED'),
                'notsub' => acym_translation('ACYM_NO_SUBSCRIPTION_STATUS'),
            ],
            'lists' => $listClass->getAllForSelect(),
            'date' => [
                'subscription_date' => acym_translation('ACYM_SUBSCRIPTION_DATE'),
                'unsubscribe_date' => acym_translation('ACYM_UNSUBSCRIPTION_DATE'),
            ],
        ];

        $conditions['user']['acy_list'] = new stdClass();
        $conditions['user']['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $conditions['user']['acy_list']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['acy_list']->option .= acym_select(
            $list['type'],
            'acym_condition[conditions][__numor__][__numand__][acy_list][action]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['acy_list']->option .= acym_select(
            $list['lists'],
            'acym_condition[conditions][__numor__][__numand__][acy_list][list]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $conditions['user']['acy_list']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-min]');
        $conditions['user']['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['user']['acy_list']->option .= '<div class="intext_select_automation">';
        $conditions['user']['acy_list']->option .= acym_select(
            $list['date'],
            'acym_condition[conditions][__numor__][__numand__][acy_list][date-type]',
            null,
            'class="intext_select_automation acym__select cell"'
        );
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['user']['acy_list']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-max]');

        $conditions['classic']['acy_list_all'] = new stdClass();
        $conditions['classic']['acy_list_all']->name = acym_translation('ACYM_NUMBER_USERS_LIST');
        $conditions['classic']['acy_list_all']->option = '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_THERE_IS').'</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            ['>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN'), '=' => acym_translation('ACYM_EXACTLY')],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][operator]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<input type="number" min="0" class="intext_input_automation cell" name="acym_condition[conditions][__numor__][__numand__][acy_list_all][number]">';
        $conditions['classic']['acy_list_all']->option .= '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_ACYMAILING_USERS').'</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="cell grid-x grid-margin-x margin-left-0" style="margin-bottom: 0"><div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            $list['type'],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][action]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            $list['lists'],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][list]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $conditions['classic']['acy_list_all']->option .= '</div></div>';
        $conditions['classic']['acy_list_all']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $conditions['classic']['acy_list_all']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list_all][date-min]');
        $conditions['classic']['acy_list_all']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            $list['date'],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][date-type]',
            null,
            'class="intext_select_automation acym__select cell"'
        );
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['classic']['acy_list_all']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list_all][date-max]');
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $listClass = new ListClass();
        $list = [
            'type' => [
                'sub' => acym_translation('ACYM_SUBSCRIBED'),
                'unsub' => acym_translation('ACYM_UNSUBSCRIBED'),
                'notsub' => acym_translation('ACYM_NO_SUBSCRIPTION_STATUS'),
            ],
            'lists' => $listClass->getAllForSelect(),
            'date' => [
                'subscription_date' => acym_translation('ACYM_SUBSCRIPTION_DATE'),
                'unsubscribe_date' => acym_translation('ACYM_UNSUBSCRIPTION_DATE'),
            ],
        ];

        $filters['acy_list'] = new stdClass();
        $filters['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $filters['acy_list']->option = '<div class="intext_select_automation cell">';
        $filters['acy_list']->option .= acym_select(
            $list['type'],
            'acym_action[filters][__numor__][__numand__][acy_list][action]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<div class="intext_select_automation cell">';
        $filters['acy_list']->option .= acym_select(
            $list['lists'],
            'acym_action[filters][__numor__][__numand__][acy_list][list]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $filters['acy_list']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][acy_list][date-min]');
        $filters['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $filters['acy_list']->option .= '<div class="intext_select_automation">';
        $filters['acy_list']->option .= acym_select(
            $list['date'],
            'acym_action[filters][__numor__][__numand__][acy_list][date-type]',
            null,
            'class="intext_select_automation acym__select cell"'
        );
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $filters['acy_list']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][acy_list][date-max]');
        $filters['acy_list']->option .= '</div>';

        if ($this->config->get('require_confirmation', '1') === '1') {
            $filters['unconfirmed'] = new stdClass();
            $filters['unconfirmed']->name = acym_translation('ACYM_UNCONFIRMED_SUBSCRIBERS');
            // The count results doesn't show up if there are no options
            $filters['unconfirmed']->option = '<input type="hidden" name="acym_action[filters][__numor__][__numand__][unconfirmed][countresults]" />';
        }
    }

    public function onAcymDeclareActions(&$actions)
    {
        $listClass = new ListClass();

        $listActions = [
            'sub' => acym_translation('ACYM_SUBSCRIBE_USERS_TO'),
            'remove' => acym_translation('ACYM_REMOVE_USERS_FROM'),
            'unsub' => acym_translation('ACYM_UNSUBSCRIBE_USERS_TO'),
        ];
        $lists = $listClass->getAllForSelect();

        $actions['acy_list'] = new stdClass();
        $actions['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $actions['acy_list']->option = '<div class="intext_select_automation cell">';
        $actions['acy_list']->option .= acym_select($listActions, 'acym_action[actions][__and__][acy_list][list_actions]', null, 'class="acym__select"');
        $actions['acy_list']->option .= '</div>';
        $actions['acy_list']->option .= '<div class="intext_select_automation cell">';
        $actions['acy_list']->option .= acym_select($lists, 'acym_action[actions][__and__][acy_list][list_id]', null, 'class="acym__select"');
        $actions['acy_list']->option .= '</div>';

        $actions['subscribe_followup'] = new stdClass();
        $actions['subscribe_followup']->name = acym_translation('ACYM_SUBSCRIBE_FOLLOW_UP');

        $followupClass = new FollowupClass();
        $allListFollowups = $followupClass->getAll();

        $actions['subscribe_followup']->option = '<div class="intext_select_automation cell">'.acym_select(
                $allListFollowups,
                'acym_action[actions][__and__][subscribe_followup][followup_id]',
                null,
                [
                    'class' => 'acym__select',
                    'data-placeholder' => (!empty($listFollowups) ? acym_translation('ACYM_SELECT_FOLLOWUP', true) : acym_translation('ACYM_FOLLOWUP_NOT_FOUND', true)),
                ],
                'id',
                'name'
            ).'</div>';
    }

    private function _processConditionAcyLists(&$query, &$options, $num)
    {
        $otherConditions = '';
        if (!empty($options['date-min'])) {
            $options['date-min'] = acym_replaceDate($options['date-min']);
            if (!is_numeric($options['date-min'])) {
                $options['date-min'] = strtotime($options['date-min']);
            }
            if (!empty($options['date-min'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' > '.acym_escapeDB(acym_date($options['date-min'], 'Y-m-d H:i:s', false));
            }
        }
        if (!empty($options['date-max'])) {
            $options['date-max'] = acym_replaceDate($options['date-max']);
            if (!is_numeric($options['date-max'])) {
                $options['date-max'] = strtotime($options['date-max']);
            }
            if (!empty($options['date-max'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' < '.acym_escapeDB(acym_date($options['date-max'], 'Y-m-d H:i:s', false));
            }
        }

        $query->leftjoin['list'.$num] = '#__acym_user_has_list as userlist'.$num.' ON user.id = userlist'.$num.'.user_id AND userlist'.$num.'.list_id = '.intval(
                $options['list']
            ).$otherConditions;
        if ($options['action'] == 'notsub') {
            $query->where[] = 'userlist'.$num.'.user_id IS NULL';
        } else {
            $status = $options['action'] == 'sub' ? '1' : '0';
            $query->where[] = 'userlist'.$num.'.status = '.intval($status);
        }

        return $query->count();
    }

    public function onAcymProcessCondition_acy_list(&$query, &$options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processConditionAcyLists($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessCondition_acy_list_all(&$query, &$options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processConditionAcyLists($query, $options, $num);

        $res = false;
        switch ($options['operator']) {
            case '=' :
                $res = $affectedRows == $options['number'];
                break;
            case '>' :
                $res = $affectedRows > $options['number'];
                break;
            case '<' :
                $res = $affectedRows < $options['number'];
                break;
        }

        if (!$res) $conditionNotValid++;
    }

    public function onAcymProcessFilter_unconfirmed(&$query, &$options, $num)
    {
        $query->where[] = 'user.confirmed = 0';
    }

    public function onAcymProcessFilterCount_unconfirmed(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_unconfirmed($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_acy_list(&$query, &$options, $num)
    {
        $this->_processConditionAcyLists($query, $options, $num);
    }

    public function onAcymProcessFilterCount_acy_list(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_list($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessAction_acy_list(&$query, $action)
    {
        if ($action['list_actions'] == 'sub') {
            $queryToProcess = 'INSERT IGNORE #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) ('.$query->getQuery(
                    [
                        'user.id',
                        $action['list_id'],
                        '1',
                        acym_escapeDB(acym_date(time(), 'Y-m-d H:i:s')),
                    ]
                ).') ON DUPLICATE KEY UPDATE status = 1';
        } elseif ($action['list_actions'] == 'remove') {
            $queryToProcess = 'DELETE FROM #__acym_user_has_list WHERE list_id = '.intval($action['list_id']).' AND user_id IN ('.$query->getQuery(['user.id']).')';
        } elseif ($action['list_actions'] == 'unsub') {
            $queryToProcess = 'UPDATE #__acym_user_has_list SET status = 0 WHERE list_id = '.intval($action['list_id']).' AND user_id IN ('.$query->getQuery(['user.id']).')';
        }

        $nbAffected = acym_query($queryToProcess);

        return acym_translationSprintf('ACYM_ACTION_LIST_'.strtoupper($action['list_actions']), $nbAffected);
    }

    public function onAcymProcessAction_subscribe_followup(&$query, &$action)
    {
        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($action['followup_id']);
        if (!empty($followup)) {

            $queryToProcess = 'INSERT IGNORE #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) ('.$query->getQuery(
                    [
                        'user.id',
                        $followup->list_id,
                        '1',
                        acym_escapeDB(acym_date(time(), 'Y-m-d H:i:s')),
                    ]
                ).') ON DUPLICATE KEY UPDATE status = 1';

            $nbAffected = acym_query($queryToProcess);

            $followups = $followupClass->getFollowupsWithMailsInfoByIds($action['followup_id']);
            foreach ($followups as $mails) {
                foreach ($mails as $mail) {
                    $sendDate = time() + (intval($mail->delay) * intval($mail->delay_unit));
                    $sendDate = acym_escapeDB(acym_date($sendDate, 'Y-m-d H:i:s', false));
                    $queryToProcess = 'INSERT IGNORE #__acym_queue (`mail_id`, `user_id`, `sending_date`, `priority`, `try`) ('.$query->getQuery(
                            [
                                $mail->mail_id,
                                'user.id',
                                $sendDate,
                                $this->config->get('priority_newsletter', 3),
                                0,
                            ]
                        ).')';

                    acym_query($queryToProcess);
                }
            }

            return acym_translationSprintf('ACYM_ACTION_LIST_SUB', $nbAffected);
        }
    }

    private function _summaryDate($automation, $finalText)
    {
        if (!empty($automation['date-min']) || !empty($automation['date-max'])) {
            $finalText .= acym_translationSprintf('ACYM_WHERE_DATE_ACY_LIST_SUMMARY', acym_strtolower(acym_translation('ACYM_'.strtoupper($automation['date-type']))));

            $dates = [];
            if (!empty($automation['date-min'])) {
                $automation['date-min'] = acym_replaceDate($automation['date-min']);
                $dates[] = acym_translationSprintf('ACYM_WHERE_DATE_MIN_ACY_LIST_SUMMARY', acym_date($automation['date-min'], 'd M Y H:i'));
            }
            if (!empty($automation['date-max'])) {
                $automation['date-max'] = acym_replaceDate($automation['date-max']);
                $dates[] = acym_translationSprintf('ACYM_WHERE_DATE_MAX_ACY_LIST_SUMMARY', acym_date($automation['date-max'], 'd M Y H:i'));
            }

            $finalText .= ' '.implode(' '.acym_strtolower(acym_translation('ACYM_AND')).' ', $dates);
        }

        return $finalText;
    }

    public function onAcymDeclareSummary_conditions(&$automation)
    {
        if (!empty($automation['acy_list_all'])) {
            $operators = ['=' => acym_translation('ACYM_EXACTLY'), '>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN')];
            $finalText = acym_translation('ACYM_THERE_IS').' '.acym_strtolower(
                    $operators[$automation['acy_list_all']['operator']]
                ).' '.$automation['acy_list_all']['number'].' '.acym_translation('ACYM_ACYMAILING_USERS').' ';
            $listClass = new ListClass();
            $automation['acy_list_all']['list'] = $listClass->getOneById($automation['acy_list_all']['list']);
            if (empty($automation['acy_list_all']['list'])) {
                $automation = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';

                return;
            }
            if ($automation['acy_list_all']['action'] == 'sub') $automation['acy_list_all']['action'] = 'ACYM_SUBSCRIBED';
            if ($automation['acy_list_all']['action'] == 'unsub') $automation['acy_list_all']['action'] = 'ACYM_UNSUBSCRIBED';
            if ($automation['acy_list_all']['action'] == 'notsub') $automation['acy_list_all']['action'] = 'ACYM__NOT_SUBSCRIBED';
            $finalText .= acym_translationSprintf(
                    'ACYM_CONDITION_ACY_LIST_SUMMARY',
                    acym_translation($automation['acy_list_all']['action']),
                    $automation['acy_list_all']['list']->name
                ).' ';

            $automation = $this->_summaryDate($automation['acy_list_all'], $finalText);
        }

        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_CONDITION_ACY_LIST_SUMMARY', 'ACYM_IS_SUBSCRIBED', 'ACYM_IS_UNSUBSCRIBED', 'ACYM_IS_NOT_SUBSCRIBED');
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_FILTER_ACY_LIST_SUMMARY', 'ACYM_SUBSCRIBED', 'ACYM_UNSUBSCRIBED', 'ACYM_NOT_SUBSCRIBED');
    }

    private function onAcymDeclareSummary_conditionsFilters(&$automation, $key, $keySub, $keyUnsub, $keyNotSub)
    {
        if (!empty($automation['acy_list'])) {
            $finalText = '';
            $listClass = new ListClass();
            $automation['acy_list']['list'] = $listClass->getOneById($automation['acy_list']['list']);
            if (empty($automation['acy_list']['list'])) {
                $automation = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';

                return;
            }
            if ($automation['acy_list']['action'] == 'sub') $automation['acy_list']['action'] = $keySub;
            if ($automation['acy_list']['action'] == 'unsub') $automation['acy_list']['action'] = $keyUnsub;
            if ($automation['acy_list']['action'] == 'notsub') $automation['acy_list']['action'] = $keyNotSub;
            $finalText .= acym_translationSprintf(
                    $key,
                    acym_translation($automation['acy_list']['action']),
                    $automation['acy_list']['list']->name
                ).' ';

            $automation = $this->_summaryDate($automation['acy_list'], $finalText);
        }

        if (!empty($automation['unconfirmed'])) {
            $automation = acym_translation('ACYM_ACTION_UNCONFIRM');
        }
    }

    public function onAcymDeclareSummary_actions(&$automationAction)
    {
        if (!empty($automationAction['acy_list'])) {
            $listClass = new ListClass();
            $list = $listClass->getOneById($automationAction['acy_list']['list_id']);
            if ($automationAction['acy_list']['list_actions'] == 'sub') $automationAction['acy_list']['list_actions'] = 'ACYM_SUBSCRIBED_TO';
            if ($automationAction['acy_list']['list_actions'] == 'unsub') $automationAction['acy_list']['list_actions'] = 'ACYM_UNSUBSCRIBE_FROM';
            if ($automationAction['acy_list']['list_actions'] == 'remove') $automationAction['acy_list']['list_actions'] = 'ACYM_REMOVE_FROM';
            if (empty($list)) {
                $automationAction = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';
            } else {
                $automationAction = acym_translationSprintf('ACYM_ACTION_LIST_SUMMARY', acym_translation($automationAction['acy_list']['list_actions']), $list->name);
            }
        }

        if (!empty($automationAction['subscribe_followup'])) {
            $followupClass = new FollowupClass();
            $followup = $followupClass->getOneById($automationAction['subscribe_followup']['followup_id']);
            $automationAction = (!empty($followup)
                ? acym_translationSprintf('ACYM_ACTION_SUBSCRIBE_FOLLOWUP_SUMMARY', $followup->name)
                : acym_translation(
                    'ACYM_FOLLOWUP_NOT_FOUND'
                ));
        }
    }

    public function onAcymAfterUserSubscribe(&$user, $lists)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger('user_subscribe', ['userId' => $user->id]);

        $followupClass = new FollowupClass();
        $followupClass->addFollowupEmailsQueue(self::FOLLOWTRIGGER, $user->id, ['sub_lists' => $lists]);
    }

    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        foreach ($followups as $key => $followup) {
            if (!empty($followup->condition['lists_status']) && !empty($followup->condition['lists'])) {
                $status = $followup->condition['lists_status'] == 'is';
                if ($followup->trigger == self::FOLLOWTRIGGER) {
                    $user = false;
                    foreach ($followup->condition['lists'] as $list) {
                        if (in_array($list, $params['sub_lists'])) {
                            $user = true;
                            break;
                        }
                    }
                } else {
                    $lists = implode(',', $followup->condition['lists']);
                    $user = acym_loadObject('SELECT * FROM #__acym_user_has_list WHERE user_id = '.intval($userId).' AND status = 1 AND list_id IN ('.$lists.')');
                }
                if (($status && empty($user)) || (!$status && !empty($user))) unset($followups[$key]);
            }
        }
    }

    public function onAcymAfterUserUnsubscribe(&$user, $lists)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger('user_unsubscribe', ['userId' => $user->id]);
    }


    public function getFollowupTriggers(&$triggers)
    {
        $triggers[self::FOLLOWTRIGGER] = acym_translation('ACYM_USER_SUBSCRIBE');
    }

    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_USER_SUBSCRIBE'),
            'description' => acym_translation('ACYM_USER_SUBSCRIBE_DESC'),
            'icon' => 'acymicon-user-check',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.self::FOLLOWTRIGGER),
            'level' => 2,
            'alias' => self::FOLLOWTRIGGER,
        ];
    }

    private function getUserClass()
    {
        if ($this->userClass === null) {
            $this->userClass = new UserClass();
        }

        return $this->userClass;
    }
}
