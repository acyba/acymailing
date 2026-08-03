<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
if (!acym_level(ACYM_ESSENTIAL)) {
    include acym_getView('configuration', 'upgrade_license', true);
}
