<?php
if (strpos($this->content, 'acym__wysid__template') !== false) {
    echo $this->content;
} else {
    echo $this->defaultTemplate;
}
