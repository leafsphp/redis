<?php

namespace Leaf\Redis\Commands;

use Aloe\Command;

class ServeCommand extends Command
{
    protected static $defaultName = 'redis:serve';
    public $description = 'Start redis server';
    public $help = 'Start redis server';

    protected function config()
    {
        $this->setOption('port', 'p', 'optional', 'Port to run redis server on', 6379);
        // $this->setArgument('config', 'optional', 'path/to/redis.conf');
    }

    protected function handle()
    {
        $port = $this->option('port');
        // $config = $this->argument('config');
        $redisVersion = shell_exec('redis-server --version');

        $command = "redis-server --port $port";

        // $config = $config ?? "Default";

        $this->writeln('Redis Server started on port ' . asComment((string) $port));
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
