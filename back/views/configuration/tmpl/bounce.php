<?php
if (!acym_level(2)) {
    $data['version'] = 'enterprise';
    echo '<div class="margin-top-1">';
    include acym_getView('dashboard', 'upgrade');
    echo '</div>';
} ?>
