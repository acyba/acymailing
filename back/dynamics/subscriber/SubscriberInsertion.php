<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\FieldClass;

trait SubscriberInsertion
{
    /**
     * Array of fields loaded to have the right option value
     */
    var $fields = [];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_SUBSCRIBER');
    }

    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        ?>
		<script type="text/javascript">
            var selectedSubscriberDText;

            function changeSubscriberTag(tagname, element) {
                if (!tagname) return;

                selectedSubscriberDText = tagname;

                var baseTag = '<?php echo $this->name; ?>';
                var $inputType = jQuery('input[name="typeInfoSubscriber"]:checked');
                if ($inputType.length > 0 && $inputType.val() === 'current') {
                    baseTag = 'user';
                }
                var finalTag = '{' + baseTag + ':' + tagname;

                if ($inputType.length > 0 && $inputType.val() !== 'current') {
                    finalTag += '|info:' + $inputType.val() + '';
                }
                finalTag += '}';

                setTag(finalTag, element);
            }
		</script>
        <?php
        $fieldClass = new FieldClass();
        $fieldsUser = acym_getColumns('user');
        $fieldsStats = acym_getColumns('user_stat');
        $fields = array_merge($fieldsUser, $fieldsStats);
        $customFields = $fieldClass->getAllFieldsForUser();
        $descriptions = [];
        $isAutomationAdmin = acym_getVar('string', 'automation');
        $mailType = acym_getVar('string', 'mail_type', MailClass::TYPE_STANDARD);
        $typeNotif = acym_getVar('string', 'notification', '');

        foreach ($customFields as $one) {
            $descriptions[$one->namekey] = acym_translation('ACYM_CUSTOM_FIELD');
            $fields[] = $one;
        }

        $descriptions['id'] = acym_translation('ACYM_USER_ID');
        $descriptions['email'] = acym_translation('ACYM_USER_EMAIL');
        $descriptions['name'] = acym_translation('ACYM_USER_NAME');
        $descriptions['cms_id'] = acym_translation('ACYM_USER_CMSID');
        $descriptions['source'] = acym_translation('ACYM_USER_SOURCE');
        $descriptions['confirmed'] = acym_translation('ACYM_USER_CONFIRMED');
        $descriptions['active'] = acym_translation('ACYM_USER_ACTIVE');
        $descriptions['creation_date'] = acym_translation('ACYM_USER_CREATION_DATE');
        $descriptions['open_date'] = acym_translation('ACYM_USER_OPEN_DATE');
        $descriptions['date_click'] = acym_translation('ACYM_USER_CLICK_DATE');
        $descriptions['send_date'] = acym_translation('ACYM_USER_SEND_DATE');

        echo '<div class="acym__popup__listing text-center grid-x">';
        if (!empty($isAutomationAdmin) || ($mailType == 'notification' && $typeNotif != 'acy_confirm')) {
            $textTrigger = $mailType == 'notification' ? 'ACYM_USER_TRIGGERING_NOTIFICATION' : 'ACYM_USER_TRIGGERING_AUTOMATION';
            $typeinfo = [];
            $typeinfo[] = acym_selectOption('receiver', 'ACYM_RECEIVER_INFORMATION');
            $typeinfo[] = acym_selectOption('current', $textTrigger);
            echo acym_radio(
                $typeinfo,
                'typeInfoSubscriber',
                'receiver',
                ['onclick' => 'changeSubscriberTag(selectedSubscriberDText, jQuery(this))']
            );
        }
        echo '<h1 class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_RECEIVER_INFORMATION').'</h1>
					';

        $others = [];
        $others['name|part:first|ucfirst'] = ['name' => acym_translation('ACYM_USER_FIRSTPART'), 'desc' => acym_translation('ACYM_USER_FIRSTPART_DESC')];
        $others['name|part:last|ucfirst'] = ['name' => acym_translation('ACYM_USER_LASTPART'), 'desc' => acym_translation('ACYM_USER_LASTPART_DESC')];

        foreach ($others as $tagname => $tag) {
            echo '<div style="cursor:pointer" class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeSubscriberTag(\''.$tagname.'\', jQuery(this));">
					<div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['name'].'</div>
					<div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['desc'].'</div>
				</div>';
        }

        foreach ($fields as $field) {
            $fieldKey = is_object($field) ? $field->namekey : $field;
            $fieldName = is_object($field) ? acym_translation($field->name) : $field;
            if (empty($descriptions[$fieldKey])) {
                continue;
            }

            $type = '';
            if (in_array($fieldKey, ['creation_date', 'open_date', 'date_click', 'send_date'])) {
                $type = '|type:time';
            }

            echo '<div style="cursor:pointer" class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeSubscriberTag(\''.$fieldKey.$type.'\', jQuery(this));">
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_escape($fieldName).'</div>
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_escape($descriptions[$fieldKey]).'</div>
                     </div>';
        }

        echo '</div>';
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, $this->name);
        $backwardsTags = $this->pluginHelper->extractTags($email, 'subtag');
        foreach ($backwardsTags as $tag => $params) {
            $extractedTags[$tag] = $params;
        }

        if (empty($extractedTags)) return;

        $userClass = new UserClass();
        $user = $userClass->getAllUserFields($user);

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) continue;

            if (!empty($oneTag->info) && $oneTag->info == 'current') continue;
            $tags[$i] = empty($user->id) ? $oneTag->default : $this->replaceSubTag($oneTag, $user);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function replaceSubTag(&$mytag, $user)
    {
        $fieldClass = new FieldClass();
        $field = $mytag->id;
        $replaceme = (isset($user->$field) && strlen($user->$field) > 0) ? $user->$field : $mytag->default;
        $replaceme = acym_translation(nl2br($replaceme));

        $this->pluginHelper->formatString($replaceme, $mytag);

        return $replaceme;
    }
}
