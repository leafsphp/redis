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
     * Increment a key's integer value
     *
     * @param string $key The key to increment
     * @param int $by Amount to increment by
     * @return int The new value
     */
    public function increment(string $key, int $by = 1): int;

    /**
     * Decrement a key's integer value
     *
     * @param string $key The key to decrement
     * @param int $by Amount to decrement by
     * @return int The new value
     */
    public function decrement(string $key, int $by = 1): int;

    /**
     * Set a time to live on an existing key
     *
     * @param string $key The key to expire
     * @param int $seconds Seconds until the key expires
     * @return bool
     */
    public function expire(string $key, int $seconds): bool;

    /**
     * Get the remaining time to live of a key
     *
     * @param string $key The key to check
     * @return int Seconds remaining, -1 if no ttl, -2 if the key doesn't exist
     */
    public function ttl(string $key): int;

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
