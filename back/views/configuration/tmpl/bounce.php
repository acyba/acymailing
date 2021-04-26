<?php
if (!acym_level(ACYM_ENTERPRISE)) {
    $data['isEnterprise'] = false;
    echo '<div class="margin-top-1">';
    include acym_getView('bounces', 'splashscreen');
    echo '</div>';
} ?>
