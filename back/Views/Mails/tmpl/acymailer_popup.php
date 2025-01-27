<div class="cell grid-x align-center" id="acym__template__import">
    <div class="cell margin-top-2 margin-bottom-2 large-7 medium-10 grid-x" style="flex-direction: column">
        <div>
            <h3><?php echo acym_translation('ACYM_WARNING'); ?></h3>
            <div class="margin-top-1">
                <?php echo acym_translation('ACYM_FIRST_ACYMAILER_USE'); ?>
            </div>
            <div class="margin-top-1">
                <?php echo acym_translation('ACYM_AVOID'); ?>
            </div>
            <div class="margin-top-1">
                <ul>
                    <li><?php echo acym_translation('ACYM_UNSUB_LINK'); ?></li>
                    <li><?php echo acym_translation('ACYM_RUN_SPAM_TEST_SENTENCE'); ?></li>
                    <li><?php echo acym_translation('ACYM_USE_ACYCHECKER'); ?></li>
                </ul>
            </div>
        </div>
        <div class="margin-top-1">
            <button type="button" class="button acy_button_submit medium-shrink" data-task="<?php echo acym_escape($task); ?>">
                <?php echo acym_translation($buttonText); ?>
            </button>
        </div>
    </div>

</div>
<img src="<?php echo ACYM_IMAGES.'templates/spaceman_template.png'; ?>" alt="spaceman with smoke" id="acym__template__import__image__spaceman">
<img src="<?php echo ACYM_IMAGES.'templates/smoke_rocket.png'; ?>" alt="smoke of rocket" id="acym__template__import__image__smoke">

