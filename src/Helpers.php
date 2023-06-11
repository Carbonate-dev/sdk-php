<?php

namespace Carbonate;

abstract class Helpers {
    public static function all(array $arr, callable $fn) {
        foreach ($arr as $item) {
            if (!$fn($item)) {
                return false;
            }
        }

        return true;
    }

    public static function slugify($file) {
        $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file);
        $file = mb_ereg_replace("([\.]{2,})", '', $file);

        return $file;
    }
}
