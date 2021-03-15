<?php
if (!acym_level(2)) {
    $data['isEnterprise'] = false;
    echo '<div class="margin-top-1">';
    include acym_getView('bounces', 'splashscreen');
    echo '</div>';
} ?>
