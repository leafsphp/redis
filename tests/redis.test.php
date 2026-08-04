<?php

use Leaf\Redis;
use Leaf\Redis\Adapter;

/*
 * The facade is tested against an in-memory fake adapter, so no redis
 * server is needed. Adapter selection and session save-path parsing are
 * covered as pure logic.
 */

class FakeRedisAdapter implements Adapter
{
    public $store = [];
    public $connected = false;
    public $closed = false;
    public $connectConfig = null;

    public function connect(array $config = [])
    {
        $this->connected = true;
        $this->connectConfig = $config;
    }

    public function get($key)
    {
        if (is_array($key)) {
            return array_map(fn ($k) => $this->store[$k] ?? false, $key);
        }

        return $this->store[$key] ?? false;
    }

    public function set(string $key, $value, int $ttl = 0)
    {
        $this->store[$key] = $value;

        return true;
    }

    public function delete($key): bool
    {
        foreach ((array) $key as $k) {
            unset($this->store[$k]);
        }

        return true;
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function keys(): array
    {
        return array_keys($this->store);
    }

    public function flush(): bool
    {
        $this->store = [];

        return true;
    }

    public function increment(string $key, int $by = 1): int
    {
        return $this->store[$key] = (int) ($this->store[$key] ?? 0) + $by;
    }

    public function decrement(string $key, int $by = 1): int
    {
        return $this->store[$key] = (int) ($this->store[$key] ?? 0) - $by;
    }

    public function expire(string $key, int $seconds): bool
    {
        $this->ttls[$key] = $seconds;

        return array_key_exists($key, $this->store);
    }

    public function ttl(string $key): int
    {
        if (!array_key_exists($key, $this->store)) {
            return -2;
        }

        return $this->ttls[$key] ?? -1;
    }

    public $ttls = [];

    public function rawCommand($name)
    {
        return "raw:$name";
    }

    public function ping(?string $message = null)
    {
        return $message ?? true;
    }

    public function errors(): array
    {
        return [];
    }

    public function connection()
    {
        return $this->connected ? $this : null;
    }

    public function close()
    {
        $this->closed = true;
        $this->connected = false;
    }
}

function fakeRedis(): array
{
    $adapter = new FakeRedisAdapter();

    return [new Redis($adapter), $adapter];
}

test('set, get, exists, delete and keys round trip', function () {
    [$redis] = fakeRedis();

    expect($redis->set('name', 'leaf'))->toBeTrue();
    expect($redis->get('name'))->toBe('leaf');
    expect($redis->exists('name'))->toBeTrue();
    expect($redis->keys())->toBe(['name']);

    expect($redis->delete('name'))->toBeTrue();
    expect($redis->exists('name'))->toBeFalse();
    expect($redis->get('name'))->toBeFalse();
});

test('flush clears every key', function () {
    [$redis] = fakeRedis();

    $redis->set('a', '1');
    $redis->set('b', '2');

    expect($redis->flush())->toBeTrue();
    expect($redis->keys())->toBe([]);
});

test('ping reaches the adapter', function () {
    [$redis] = fakeRedis();

    expect($redis->ping())->toBeTrue();
    expect($redis->ping('hello'))->toBe('hello');
});

test('the facade lazily connects with merged config', function () {
    [$redis, $adapter] = fakeRedis();

    expect($adapter->connected)->toBeFalse();

    $redis->connect(['host' => 'redis.internal', 'port' => 6380]);
    expect($adapter->connected)->toBeFalse(); // still lazy — no session, no commands yet

    $redis->set('x', 'y');
    expect($adapter->connected)->toBeTrue();
    expect($adapter->connectConfig['host'])->toBe('redis.internal');
    expect($adapter->connectConfig['port'])->toBe(6380);
});

test('close disconnects the adapter', function () {
    [$redis, $adapter] = fakeRedis();

    $redis->set('x', 'y');
    $redis->close();

    expect($adapter->closed)->toBeTrue();
});

test('session save options are parsed into a connection query string', function () {
    [$redis, $adapter] = fakeRedis();

    $redis->connect([
        'session.saveOptions' => [
            ['prefix' => 'leaf_sess:', 'database' => 1],
        ],
    ]);

    $config = (function () {
        return $this->config;
    })->call($redis);

    expect($config['session.saveOptions'])->toBe(['?prefix=leaf_sess:&database=1']);
});

test('enabling sessions builds the save path from the connection config', function () {
    [$redis, $adapter] = fakeRedis();

    $redis->connect([
        'session' => true,
        'password' => 'secret',
        'session.saveOptions' => [
            ['prefix' => 'leaf_sess:'],
        ],
    ]);

    expect($adapter->connected)->toBeTrue();

    $config = (function () {
        return $this->config;
    })->call($redis);

    expect($config['session.savePath'])->toBe('127.0.0.1:6379?auth=secret&prefix=leaf_sess:');
    expect(ini_get('session.save_path'))->toBe($config['session.savePath']);
});

test('increment and decrement manage counters', function () {
    [$redis] = fakeRedis();

    expect($redis->increment('hits'))->toBe(1);
    expect($redis->increment('hits', 5))->toBe(6);
    expect($redis->decrement('hits', 2))->toBe(4);
});

test('expire and ttl manage key lifetimes', function () {
    [$redis] = fakeRedis();

    expect($redis->ttl('missing'))->toBe(-2);

    $redis->set('cached', 'value');
    expect($redis->ttl('cached'))->toBe(-1);

    expect($redis->expire('cached', 60))->toBeTrue();
    expect($redis->ttl('cached'))->toBe(60);
});

test('unknown commands pass through to the adapter', function () {
    [$redis] = fakeRedis();

    expect($redis->rawCommand('info'))->toBe('raw:info');
});

test('the default adapter prefers predis when installed', function () {
    $redis = new Redis();

    $connection = (function () {
        return $this->redis;
    })->call($redis);

    // predis is a dev dependency here; without it the Native (phpredis)
    // adapter is selected instead
    expect($connection)->toBeInstanceOf(
        class_exists('Predis\Client') ? \Leaf\Redis\Predis::class : \Leaf\Redis\Native::class
    );
});
