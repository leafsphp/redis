<?php

namespace Leaf;

use Leaf\Redis\Adapter;

/**
 * Leaf + Redis [BETA]
 * ----------
 * Redis made crazy simple
 *
 * @since 2.5.1
 * @author Michael Darko <mickdd22@gmail.com>
 * @version 4.0.0-beta
 */
class Redis
{
    /** @var Adapter */
    protected $redis;

    /**
     * Leaf Redis config
     * @var array
     */
    protected $config = [
        'port' => 6379,
        'scheme' => 'tcp',
        'password' => null,
        'host' => '127.0.0.1',

        'session' => false,
        'session.savePath' => null,
        'session.saveOptions' => [],

        'connection.timeout' => 0.0,
        'connection.reserved' => null,
        'connection.retryInterval' => 0,
        'connection.readTimeout' => 0.0,
    ];

    public function __construct(?Adapter $adapter = null)
    {
        $this->redis = $adapter ?? (class_exists('Predis\Client') ? new Redis\Predis() : new Redis\Native());
    }

    /**
     * Initialize redis and connect to redis instance
     *
     * @param array $config Configuration for the redis instance.
     * @return \Leaf\Redis
     */
    public function connect(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        if (!empty($this->config['session.saveOptions'])) {
            static::parseSaveOptions();
        }

        if ($this->config['session'] === true) {
            static::setSessionHandler();
        }

        return $this;
    }

    protected function setSessionHandler()
    {
        $this->redis->connect($this->config);

        if (!$this->config['session.savePath']) {
            $this->config['session.savePath'] = $this->config['host'] . ':' . $this->config['port'];

            if ($this->config['password']) {
                $this->config['session.savePath'] .= '?auth=' . $this->config['password'];
            }

            if (!empty($this->config['session.saveOptions'])) {
                $option = $this->config['session.saveOptions'][0];

                // options already start with '?' — continue with '&' when
                // the auth query string opened the parameters
                $this->config['session.savePath'] .= $this->config['password']
                    ? '&' . ltrim($option, '?')
                    : $option;
            }
        } else {
            if (is_array($this->config['session.savePath'])) {
                $fullPath = '';

                foreach ($this->config['session.savePath'] as $index => $savePath) {
                    $fullPath .= $savePath;

                    if ($this->config['session.saveOptions'][$index] ?? false) {
                        $fullPath .= $this->config['session.saveOptions'][$index];
                    }

                    if (($index + 1) < count($this->config['session.savePath'])) {
                        $fullPath .= ', ';
                    }
                }

                $this->config['session.savePath'] = $fullPath;
            }
        }

        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', $this->config['session.savePath']);

        if (function_exists('auth')) {
            ini_set('session.gc_maxlifetime', auth()->config('session.lifetime'));
        }
    }

    protected function parseSaveOptions()
    {
        $parsedOptions = [];

        foreach ($this->config['session.saveOptions'] as $options) {
            $optionKeys = array_keys($options);
            $option = '';

            foreach ($optionKeys as $optionIndex => $optionValue) {
                switch ($optionIndex) {
                    case 0:
                        $option .= "?{$optionValue}={$options[$optionValue]}";

                        break;
                    default:
                        $option .= "&{$optionValue}={$options[$optionValue]}";

                        break;
                }
            }

            $parsedOptions[] = $option;
        }

        $this->config['session.saveOptions'] = $parsedOptions;
    }

    /**
     * Set a redis value
     *
     * @param string|array $key The value(s) to set
     * @param string|mixed $value — string if not used serializer
     * @param int|array $timeout [optional] Calling setex() is preferred if you want a timeout.
     *
     * Since 2.6.12 it also supports different flags inside an array. Example ['NX', 'EX' => 60]
     * - EX seconds -- Set the specified expire time, in seconds.
     * - PX milliseconds -- Set the specified expire time, in milliseconds.
     * - NX -- Only set the key if it does not already exist.
     * - XX -- Only set the key if it already exist.
     * // Simple key -> value set $redis->set('key', 'value');
     * // Will redirect, and actually make an SETEX call $redis->set('key','value', 10);
     * // Will set the key, if it doesn't exist, with a ttl of 10 seconds $redis->set('key', 'value', ['nx', 'ex' => 10]);
     * // Will set a key, if it does exist, with a ttl of 1000 milliseconds $redis->set('key', 'value', ['xx', 'px' => 1000]);
     * @return bool — TRUE if the command is successful
     * @link https://redis.io/commands/set
     * @since If you're using Redis >= 2.6.12, you can pass extended options as explained in example
     */
    public function set($key, $value = '', $timeout = 0)
    {
        if (function_exists('crash')) {
            crash()->leaveCrumb('redis set: ' . (is_array($key) ? implode(',', array_keys($key)) : $key), 'cache', [], false);
        }

        return $this->connection()->set($key, $value, $timeout);
    }

    /**
     * Get a redis value
     *
     * @param string|array $key The value(s) to get
     * @return string|mixed|false
     * If key didn't exist, FALSE is returned. Otherwise, the value related to this key is returned
     *
     * @link https://redis.io/commands/get
     */
    public function get($key)
    {
        $value = $this->connection()->get($key);

        // misses are journey-worthy, hits are noise
        if ($value === false && !is_array($key) && function_exists('crash')) {
            crash()->leaveCrumb("redis miss: $key", 'cache', [], false);
        }

        return $value;
    }

    /**
     * Delete a key from redis
     *
     * @param string|array $key The key to delete
     * @return bool
     * @link https://redis.io/commands/del
     */
    public function delete($key): bool
    {
        if (function_exists('crash')) {
            crash()->leaveCrumb('redis delete: ' . implode(',', (array) $key), 'cache', [], false);
        }

        return $this->connection()->delete($key);
    }

    /**
     * Check if a key exists in redis
     *
     * @param string $key The key to check
     * @return bool
     * @link https://redis.io/commands/exists
     */
    public function exists(string $key): bool
    {
        return $this->connection()->exists($key);
    }

    /**
     * Get all keys in redis
     *
     * @return array
     * @link https://redis.io/commands/keys
     */
    public function keys(): array
    {
        return $this->connection()->keys();
    }

    /**
     * Increment a key's integer value
     *
     * @param string $key The key to increment
     * @param int $by Amount to increment by
     * @return int The new value
     * @link https://redis.io/commands/incrby
     */
    public function increment(string $key, int $by = 1): int
    {
        return $this->connection()->increment($key, $by);
    }

    /**
     * Decrement a key's integer value
     *
     * @param string $key The key to decrement
     * @param int $by Amount to decrement by
     * @return int The new value
     * @link https://redis.io/commands/decrby
     */
    public function decrement(string $key, int $by = 1): int
    {
        return $this->connection()->decrement($key, $by);
    }

    /**
     * Set a time to live on an existing key
     *
     * @param string $key The key to expire
     * @param int $seconds Seconds until the key expires
     * @return bool
     * @link https://redis.io/commands/expire
     */
    public function expire(string $key, int $seconds): bool
    {
        return $this->connection()->expire($key, $seconds);
    }

    /**
     * Get the remaining time to live of a key
     *
     * @param string $key The key to check
     * @return int Seconds remaining, -1 if no ttl, -2 if the key doesn't exist
     * @link https://redis.io/commands/ttl
     */
    public function ttl(string $key): int
    {
        return $this->connection()->ttl($key);
    }

    /**
     * Flush all keys in redis
     *
     * @return bool
     * @link https://redis.io/commands/flushall
     */
    public function flush(): bool
    {
        return $this->connection()->flush();
    }

    /**
     * Ping redis server.
     *
     * @param string|null $message — [optional]
     * @return bool|string
     * TRUE if the command is successful or returns message Throws a RedisException object on connectivity error, as described above
     * @throws \Exception
     * @link https://redis.io/commands/ping
     */
    public function ping(?string $message = null)
    {
        return $this->connection()->ping($message);
    }

    /**
     * Return all saved errors
     */
    public function errors(): array
    {
        return $this->connection()->errors();
    }

    /**
     * Close the redis connection
     * @return void
     */
    public function close()
    {
        $this->connection()->close();
    }

    /**
     * Get the redis connection
     * @return Adapter
     */
    public function connection(): Adapter
    {
        if (!$this->redis->connection()) {
            $this->redis->connect($this->config);
        }

        return $this->redis;
    }

    /**
     * Pass any other redis command straight to the underlying client,
     * eg. redis()->hSet('user:1', 'name', 'leaf')
     *
     * @param string $method The redis command to run
     * @param array $args Arguments for the command
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->connection()->{$method}(...$args);
    }

    /**
     * Get all leaf redis console commands
     */
    public function commands(): array
    {
        return [
            Redis\Commands\ServeCommand::class,
        ];
    }
}
