<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
if (!acym_level(ACYM_ENTERPRISE)) {
    $data['isEnterprise'] = false;
    echo '<div class="margin-top-1">';
    include acym_getView('bounces', 'splashscreen');
    echo '</div>';
}
