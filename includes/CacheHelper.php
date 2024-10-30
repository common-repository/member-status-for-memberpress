<?php

namespace Nakko\MeprMemberStatus;
/**
 * WordPress cache utility functions
 */
class CacheHelper {

    /**
     * Gets or Sets a variable in the WordPress object cache using the given callback function
     *
     * @param $key string Cache key
     * @param $group string Cache Group
     * @param $expiration int cache expiration
     * @param $cb () callback function
     * @return bool|mixed|string|null
     */
    public function cache_get_set(string $key, string $group = '', int $expiration = 0, callable $cb = null) {
        $found = false;
        $cached_value = wp_cache_get($key, $group, false, $found);
        if ($found) {
            return $cached_value;
        } else if (isset($cb)) {
            $value = $cb();
            wp_cache_set($key, $value, $group, $expiration);
            return $value;
        }
        return null;
    }

}