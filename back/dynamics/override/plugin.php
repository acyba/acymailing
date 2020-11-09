<?php

use AcyMailing\Classes\OverrideClass;
use AcyMailing\Libraries\acymPlugin;

class plgAcymOverride extends acymPlugin
{
    /**
     * Array of fields loaded to have the right option value
     */
    var $fields = [];
    const TRIGGERS = [
        'user_creation' => 'ACYM_ON_USER_CREATION',
        'user_modification' => 'ACYM_ON_USER_MODIFICATION',
        'user_click' => 'ACYM_WHEN_USER_CLICKS_MAIL',
        'user_open' => 'ACYM_WHEN_USER_OPEN_MAIL',
        'user_subscribe' => 'ACYM_WHEN_USER_SUBSCRIBES',
        'user_unsubscribe' => 'ACYM_WHEN_USER_UNSUBSCRIBES',
        'user_confirmation' => 'ACYM_WHEN_USER_CONFIRMS_SUBSCRIPTION',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_OVERRIDES');
    }

    public function dynamicText($mailId)
    {
        $overridesClass = new OverrideClass();
        $overrideParams = $overridesClass->getParamsByMailId($mailId);
        if (!empty($overrideParams)) {
            return $this->pluginDescription;
        }
    }


    public function textPopup()
    {
        ?>

		<script language="javascript" type="text/javascript">
            <!--
            var selectedTag;

            function changeOverrideTag(tagname, element) {
                if (!tagname) return;
                setTag('{' + tagname + '}', element);
            }

            -->
		</script>

        <?php
        $mailId = acym_getVar('int', 'id', 0);

        if (empty($mailId)) {
            echo '';
            exit;
        }

        $text = '<div class="acym__popup__listing text-center grid-x">';
        $text .= '<h1 class="acym__popup__plugin__title cell">'.acym_translation('ACYM_ORIGINAL_EMAIL_DATA').'</h1>';

        $overridesClass = new OverrideClass();
        $overrideParams = $overridesClass->getParamsByMailId($mailId);

        foreach ($overrideParams as $key => $overrideParam) {
            $text .= '<div style="cursor:pointer" class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeOverrideTag(\''.$key.'\', jQuery(this));" >
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$overrideParam['nicename'].'</div>
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$overrideParam['description'].'</div>
                     </div>';
        }

        $text .= '</div>';

        echo $text;
    }
}
