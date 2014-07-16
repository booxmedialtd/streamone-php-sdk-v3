<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

require_once("StreamOneCacheInterface.php");

/**
 * A no-op caching implementation, caching nothing
 */
class StreamOneNoopCache implements StreamOneCacheInterface
{
    /**
     * Get the value of a stored key
     * 
     * @param string $key Key to get the cached value of
     * @return mixed Cached value of the key, or false if value not found or expired
     */
    public function get($key)
    {
        return false;
    }
    
     /**
     * Get the age of a stored key
     *
     * @param string $key Key to get the age of
     * @return mixed Age of the key, or false if value not found or expired
     */
    public function age($key)
    {
        return false;
    }
    
    /**
     * Store a value for the given key
     * 
     * Storing a value may not guarantee it being available, so first storing a value and then
     * immediately retrieving it may still not give a valid result. For example, the
     * StreamOneNoopCache stores nothing so get(...) will never return any value.
     * 
     * @param string $key Key to cache the value for
     * @param mixed $value Value to store for the given key
     */
    public function set($key, $value)
    {
        ; // Unfortunately, PHP has no skip or noop statement
    }
}

/**
 * @}
 */
