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

    private function getBodyElement(): \DOMElement
    {
        $node = $this->getDomDocument()->getElementsByTagName('body')->item(0);
        if (!$node instanceof \DOMElement) {
            throw new \RuntimeException('There is no body element.', 1617922607);
        }

        return $node;
    }

    private function removeSelfClosingTagsClosingTags(string $html): string
    {
        return \preg_replace('%</'.self::PHP_UNRECOGNIZED_VOID_TAGNAME_MATCHER.'>%', '', $html);
    }

    public function getMatchingUninlinableSelectors(): array
    {
        $selectors = parent::getMatchingUninlinableSelectors();
        $selectors[] = '.hideonline';
        $selectors[] = '#acym__wysid__template center > table';

        return $selectors;
    }
}
