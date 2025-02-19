<?php

namespace Leaf\Redis;

use Leaf\Redis\Adapter;

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
            $this->redis = new \Predis\Client([
                'scheme' => $this->config['scheme'],
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'password' => $this->config['password'],
                'timeout' => $this->config['connection.timeout'],
                'reserved' => $this->config['connection.reserved'],
                'retry_interval' => $this->config['connection.retryInterval'],
                'read_write_timeout' => $this->config['connection.readTimeout'],
            ]);
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

    public function flush(): bool
    {
        return $this->redis->flushdb();
    }

    public function ping(?string $message = null)
    {
        return $this->redis->ping($message);
    }

    public function errors(): array
    {
        return $this->errors;
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
