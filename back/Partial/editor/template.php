<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
if (strpos($this->content, 'acym__wysid__template') !== false) {
    $bodyPos = strpos($this->content, '<body>');
    $endBodyPos = strpos($this->content, '</body>');

    $emailContent = $this->content;

    if ($bodyPos !== false && $endBodyPos !== false) {
        $emailContent = substr(substr($this->content, 0, $endBodyPos), $bodyPos + 6);
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw newsletter content built by the user with the email editor.
    echo $emailContent;
} else {
    include acym_getPartial('editor', 'default_template');
}
