<?php

function acym_strtolower(string $string): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($string);
    } else {
        return strtolower($string);
    }
}
