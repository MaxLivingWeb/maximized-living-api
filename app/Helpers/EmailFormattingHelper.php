<?php

namespace App\Helpers;

class EmailFormattingHelper
{
    /**
     *
     * Formats emails with bold / yellow background for fields that have changed
     *
     * @param $before - the value before update
     * @param $after - the value after update
     * @param $type
     *
     * @return string
     */
    public static function compareLocationChange($before='',$after='') {
        $changeStyle = 'style="font-weight: bold;background-color:yellow;"';
        if ($before !== $after) {// return style for changed field
            return $changeStyle;
        }
    }
}
