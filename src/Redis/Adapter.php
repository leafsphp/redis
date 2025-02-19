<?php

namespace Leaf\Redis;

/**
 * Redis Adapter
 * -------------
 * Contract for redis adapters
 */
interface Adapter
{
    /**
     * Initialize redis and connect to redis instance
     *
     * @param array $config Configuration for the redis instance.
     * @return void
     */
    public function connect(array $config = []);

    /**
     * Get a value from redis
     *
     * @param string|array $key The key to get
     * @return mixed
     */
    public function get($key);

    /**
     * Set a value in redis
     *
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @param int $ttl The time to live for the key
     * @return mixed
     */
    public function set(string $key, $value, int $ttl = 0);

    /**
     * Delete a key from redis
     *
     * @param string|array $key The key to delete
     * @return bool
     */
    public function delete($key): bool;

    /**
     * Check if a key exists in redis
     *
     * @param string $key The key to check
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Get all keys in redis
     *
     * @return array
     */
    public function keys(): array;

    /**
     * Flush all keys in redis
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Ping the redis server
     *
     * @param string|null $message The message to send
     * @return string|false
     */
    public function ping(?string $message = null);

    /**
     * Get all saved errors
     *
     * @return array
     */
    public function errors(): array;

    /**
     * Get the redis connection
     *
     * @return mixed
     */
    public function connection();

    /**
     * Close the redis connection
     *
     * @return void
     */
    public function close();
}
