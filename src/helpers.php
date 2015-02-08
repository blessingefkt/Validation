<?php

/**
 * @param $name
 * @param bool $append
 * @return \Illuminate\Contracts\Support\MessageBag
 */
function mapMsgsToKey(array $msgs, $name, $append = false)
{
    $toMerge = [];
    foreach ($msgs as $key => $errors) {
        $toMerge["{$name}.{$key}"] = array_map(function ($msg) use ($name, $key, $append) {
            $replace = ($append) ? $key . ' ' . $name : $name . ' ' . $key;
            return str_replace($key, $replace, $msg);
        }, $errors);
    }
    return $toMerge;
}
