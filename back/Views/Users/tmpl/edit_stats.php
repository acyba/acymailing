<?php
// context verification
?>
<div class="cell grid-x align-middle text-center acym__users__display__click acym__content margin-bottom-1">
    <?php acym_displayRoundChart($data['percentageOpen'], 'open', 'cell small-6', acym_translation('ACYM_AVERAGE_OPEN')); ?>
    <?php acym_displayRoundChart($data['percentageClick'], 'click', 'cell small-6', acym_translation('ACYM_AVERAGE_CLICK')); ?>
</div>
