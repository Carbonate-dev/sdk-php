<?php

namespace Carbonate;

function slugify($file) {
    $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file);
    $file = mb_ereg_replace("([\.]{2,})", '', $file);

    return $file;
}