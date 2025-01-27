<?php

use AcyMailing\Classes\OverrideClass;

trait OverrideInsertion
{
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
        $mailId = acym_getVar('int', 'mail_id', 0);
        if (empty($mailId)) return;
        ?>
		<script type="text/javascript">
            function changeOverrideTag(tagname, element) {
                if (!tagname) return;
                setTag('{' + tagname + '}', element);
            }
		</script>
        <?php

        $text = '<div class="acym__popup__listing text-center grid-x">';
        $text .= '<h1 class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_ORIGINAL_EMAIL_DATA').'</h1>';

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
