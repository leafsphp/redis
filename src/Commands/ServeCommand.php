<?php

namespace Leaf\Redis\Commands;

use Leaf\Sprout\Command;

class ServeCommand extends Command
{
    protected $signature = 'redis:serve
        {--p|port=6379 : Port to run redis server on (default: 6379)}';
    protected $description = 'Start redis server';
    protected $help = 'Start redis server';

    protected function handle()
    {
        $port = $this->option('port');
        // $config = $this->argument('config');
        $redisVersion = shell_exec('redis-server --version');

        $command = "redis-server --port $port";

        // $config = $config ?? "Default";

        $this->writeln("Redis Server started on port <comment>$port</comment>");
        $this->info("Happy gardening!!\n");
        $this->comment("
                        _._
           _.-``__ ''-._
      _.-``    `.  `_.  ''-._           $redisVersion
  .-`` .-```.  ```\/    _.,_ ''-._
 (    '      ,       .-`  | `,    )     Config: Default
 |`-._`-...-` __...-.``-._|'` _.-'|     Port: $port
 |    `-._   `._    /     _.-'    |
  `-._    `-._  `-./  _.-'    _.-'      https://redis.io
 |`-._`-._    `-.__.-'    _.-'_.-'|
 |    `-._`-._        _.-'_.-'    |
  `-._    `-._`-.__.-'_.-'    _.-'
 |`-._`-._    `-.__.-'    _.-'_.-'|
 |    `-._`-._        _.-'_.-'    |
  `-._    `-._`-.__.-'_.-'    _.-'
      `-._    `-.__.-'    _.-'
          `-._        _.-'
              `-.__.-'
    ");
        $this->writeln(shell_exec($command));
    }
}
