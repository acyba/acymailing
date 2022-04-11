<?php
if (strpos($this->content, 'acym__wysid__template') !== false) {
    $bodyPos = strpos($this->content, '<body>');
    $endBodyPos = strpos($this->content, '</body>');
    if ($bodyPos !== false && $endBodyPos !== false) {
        echo substr(substr($this->content, 0, $endBodyPos), $bodyPos + 6);
    } else {
        echo $this->content;
    }
} else {
    echo $this->defaultTemplate;
}
