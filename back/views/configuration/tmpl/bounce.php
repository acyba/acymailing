<?php
if (!acym_level(2)) {
    $data['version'] = 'enterprise';
    echo '<div class="acym_area">
            <div class="acym_area_title">'.acym_translation('ACYM_BOUNCE_HANDLING').'</div>';
    include acym_getView('dashboard', 'upgrade');
    echo '</div>';
} ?>
