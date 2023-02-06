<?php

namespace AcyMailing\Helpers;

use Pelago\Emogrifier\CssInliner;

class AcyCssInliner extends CssInliner
{
    public function renderBodyContent(): string
    {
        $htmlWithPossibleErroneousClosingTags = $this->getDomDocument()->saveHTML($this->getBodyElement());

        return $this->removeSelfClosingTagsClosingTags($htmlWithPossibleErroneousClosingTags);
    }

    protected function addStyleElementToDocument(string $css): void
    {
        global $emogrifiedMediaCSS;
        $emogrifiedMediaCSS = $css;
    }
}
