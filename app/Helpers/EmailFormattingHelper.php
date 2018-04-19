<?php

namespace App\Helpers;

class EmailFormattingHelper
{
    /**
     * @param $before
     * @param $after
     * @param $type
     *
     * @return string
     */
    public static function compareLocationChange($before='',$after='',$type='') {

        if ($type !== 'update') {
            return '';
        }
        $changeStyle = 'style="font-weight: bold;background-color:yellow;"';
        if ($before !== $after) {
            return $changeStyle;
        }
    }
}
