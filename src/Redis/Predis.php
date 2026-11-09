<?php

namespace Leaf\Redis;

/**
 * Predis Redis Adapter
 * -------------
 * Redis adapter for the Predis composer package
 */
class Predis implements Adapter
{
    /** @var \Predis\Client */
    protected $redis;

    protected $config = [];

    protected $errors = [];

    public function connect(array $config = [])
    {
        $this->config = $config;

        try {
            $parameters = [
                'scheme' => $this->config['scheme'],
                'host' => $this->config['host'],
                'port' => $this->config['port'],
            ];

            if ($this->config['username']) {
                $parameters['username'] = $this->config['username'];
            }

            if ($this->config['password']) {
                $parameters['password'] = $this->config['password'];
            }

            // a 0.0 timeout means "no timeout" for phpredis, but predis reads
            // it literally and every connect dies instantly — only pass the
            // timeouts when they were actually configured
            if (($this->config['connection.timeout'] ?? 0) > 0) {
                $parameters['timeout'] = $this->config['connection.timeout'];
            }

            if (($this->config['connection.readTimeout'] ?? 0) > 0) {
                $parameters['read_write_timeout'] = $this->config['connection.readTimeout'];
            }

            $this->redis = new \Predis\Client($parameters);
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
        return (bool) $this->redis->del($key);
    }

    public function exists(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    public function keys(): array
    {
        return $this->redis->keys('*');
    }

    public function increment(string $key, int $by = 1): int
    {
        return (int) $this->redis->incrby($key, $by);
    }

    public function decrement(string $key, int $by = 1): int
    {
        return (int) $this->redis->decrby($key, $by);
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
        return ((string) $this->redis->flushdb()) === 'OK';
    }

    public function ping(?string $message = null)
    {
        if ($message !== null) {
            return $this->redis->ping($message);
        }

        return ((string) $this->redis->ping()) === 'PONG';
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
        $this->redis->disconnect();
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->redis, $method], $args);
    }
}
