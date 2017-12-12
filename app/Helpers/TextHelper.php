<?php

namespace App\Helpers;

class TextHelper
{
    public static function fixEscapeForSpecialCharacters($str)
    {
        $str = urlencode($str);

        if (strpos($str, '%40')) {
            return str_replace('%40', '@', $str);
        }
    }
}
