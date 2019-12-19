## 依赖inotify和swoole扩展
使用inotify监听目录 文件变更时自动同步到远程服务器程

```
pecl install swoole
pecl install inotify
```

## 配置说明

```
$conf = [
    'SourceFileServer' => ['host' => '192.168.8.152', 'port' => 9657],//同步文件来源的主服务器
    'FileSyncClient'   => [//需要同步到的从服务器 端口设置保持一致
        ['host' => '192.168.8.98', 'port' => 9656],
        ['host' => '192.168.8.164', 'port' => 9656],
    ],
    'FileMonitorDir'   => '/data/synctest/',//文件动态监控的目录
    'cmd'              => '/usr/local/bin/php',//执行命令
];
```

## 使用说明

- 配置文件设置完成后 将代码放置到涉及的各个服务器

- 使用前 需要先开启从服务器client

```
 php daemon.php -t c -o start
```

- 所有从服务器client 开启后 启动主服务器并开启文件监控

```
 php daemon.php -t s -o start
```

## 服务关闭

```
 php daemon.php -t c -o stop

 php daemon.php -t s -o stop
```
