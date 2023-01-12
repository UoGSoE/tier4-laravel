<?php

namespace App\Proxies;

class CachedOption
{
    public static function get($key, $default = null, $ttlSeconds = 5)
    {
        // this feels a bit bad, but to avoid tests that mess around with options failing due to cached values
        // we just disable caching when running tests
        if (app('env') === 'testing') {
            $ttlSeconds = 0;
        }
        return \Illuminate\Support\Facades\Cache::remember('option:' . $key, $ttlSeconds, function () use ($key, $default) {
            return option($key, $default);
        });
    }
}
