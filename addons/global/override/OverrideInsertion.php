<?php

use AcyMailing\Classes\OverrideClass;

trait OverrideInsertion
{
    public function dynamicText(?int $mailId): ?object
    {
        $overridesClass = new OverrideClass();
        $overrideParams = $overridesClass->getParamsByMailId($mailId);
        if (!empty($overrideParams)) {
            return $this->pluginDescription;
        }

        return null;
    }

    public function textPopup(): void
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
        echo '<div class="acym__popup__listing text-center grid-x">';
        echo '<h1 class="acym__title acym__title__secondary text-center cell">'.acym_escapeHtml(acym_translation('ACYM_ORIGINAL_EMAIL_DATA')).'</h1>';

        $overridesClass = new OverrideClass();
        $overrideParams = $overridesClass->getParamsByMailId($mailId);

        foreach ($overrideParams as $key => $overrideParam) {
            echo '<div style="cursor:pointer" 
            		class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" 
            		onclick="changeOverrideTag(\''.acym_escape($key).'\', jQuery(this));">
					<div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_escapeHtml($overrideParam['nicename']).'</div>
					<div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_escapeHtml($overrideParam['description']).'</div>
				 </div>';
        }

        echo '</div>';
    }
}
