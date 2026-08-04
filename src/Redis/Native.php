<?php

namespace Leaf\Redis;

/**
 * Native Redis Adapter
 * -------------
 * Redis adapter for the native PHP Redis extension
 */
class Native implements Adapter
{
    /** @var \Redis */
    protected $redis;

    protected $config = [];

    protected $errors = [];

    public function connect(array $config = [])
    {
        $this->config = $config;

        try {
            $this->redis = new \Redis();
            $this->redis->connect(
                $this->config['host'],
                $this->config['port'],
                $this->config['connection.timeout'],
                $this->config['connection.reserved'],
                $this->config['connection.retryInterval'],
                $this->config['connection.readTimeout']
            );

            if ($this->config['password']) {
                $this->redis->auth($this->config['password']);
            }
        } catch (\Throwable $th) {
            trigger_error($th);
        }
    }

    public function get($key)
    {
        if (is_array($key)) {
            return $this->redis->mget($key);
        }

        return $this->redis->get($key);
    }

    public function set(string $key, $value, int $ttl = 0)
    {
        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $value);
        }

        return $this->redis->set($key, $value);
    }

    public function delete($key): bool
    {
        return $this->redis->del($key);
    }

    public function exists(string $key): bool
    {
        return $this->redis->exists($key);
    }

    public function keys(): array
    {
        return $this->redis->keys('*');
    }

    public function increment(string $key, int $by = 1): int
    {
        return (int) $this->redis->incrBy($key, $by);
    }

    public function decrement(string $key, int $by = 1): int
    {
        return (int) $this->redis->decrBy($key, $by);
    }

    public function expire(string $key, int $seconds): bool
    {
        return (bool) $this->redis->expire($key, $seconds);
    }

    public function ttl(string $key): int
    {
        return (int) $this->redis->ttl($key);
    }

    public function flush(): bool
    {
        return $this->redis->flushAll();
    }

    public function ping(?string $message = null)
    {
        return $this->redis->ping($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function connection()
    {
        return $this->redis;
    }

    public function close()
    {
        $this->redis->close();
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->redis, $method], $args);
    }
}
