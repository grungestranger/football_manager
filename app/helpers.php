<?php

if (! function_exists('sign')) {
    function sign($x, $not_null = FALSE) {
        if ($x == 0 && ! $not_null) {
            return 0;
        } else {
            return $x < 0 ? -1 : 1;
        }
    }
}