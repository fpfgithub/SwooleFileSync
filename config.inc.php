<?php
$conf = [
    'SourceFileServer' => ['host' => '192.168.8.152', 'port' => 9657],
    'FileSyncClient'   => [
        ['host' => '192.168.8.98', 'port' => 9656],
        ['host' => '192.168.8.164', 'port' => 9656],
    ],
    'FileMonitorDir'   => '/data/synctest/',
    'cmd'              => '/usr/local/bin/php',
];
