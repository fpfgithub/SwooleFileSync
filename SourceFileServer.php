<?php
class SourceFileServer
{
    private $serv;
    private $conf;

    public function __construct()
    {
        date_default_timezone_set('PRC');
        require __DIR__ . '/config.inc.php';
        $this->conf = $conf;
        $this->serv = new \swoole_server($this->conf['SourceFileServer']['host'], $this->conf['SourceFileServer']['port']);
        $this->serv->set(array(
            'worker_num'            => 8,
            'daemonize'             => true,
            'max_request'           => 10000,
            'dispatch_mode'         => 1,
            'debug_mode'            => 1,
            'task_worker_num'       => 10,
            // 'log_file' => '/tmp/swoole.log',
            "socketbuffersize"      => 200 * 1024 * 1024,
            // 'open_length_check'     => true,
            'package_max_length'    => 20 * 1024 * 1024,
            // 'package_length_type'   => 'N',
            // 'package_length_offset' => 0,
            // 'package_body_offset'   => 4,

        ));
        $this->serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->serv->on('Close', array($this, 'onClose'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
    }

    public function onWorkerStart($serv, $worker_id)
    {
        echo "worker_id==>" . $worker_id . PHP_EOL;

    }

    public function onClose($serv, $fd, $from_id)
    {
        echo "Client {$fd} close connection\n";
    }

    public function onReceive($serv, $fd, $from_id, $str)
    {
        echo $str . PHP_EOL;
        $serv->task($str); //投递task任务
        // $serv->send($fd, $str ? true : false);
    }

    public function onTask($serv, $task_id, $from_id, $param)
    {
        echo "onTask..." . $task_id . PHP_EOL;
        $this->sendToClient($param);
        return $task_id;
    }

    public function onFinish($serv, $task_id, $param)
    {
        echo "onFinish==>" . $task_id . PHP_EOL;
    }

    public function sendToClient($filename)
    {
        $content     = file_get_contents($filename);
        $filecontent = "<-filename-{$filename}-filename->" . $content;
        $fileSyncClient = $this->conf['FileSyncClient'];
        foreach ($fileSyncClient as $clientConf) {
            $this->sendFileContent($filecontent, $clientConf);
        }
    }

    public function sendFileContent($filecontent, $clientConf)
    {
        SCL:
        // $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        // $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $client = new \swoole_client(SWOOLE_TCP);
        if (!$client->connect($clientConf['host'], $clientConf['port'], -1)) {
            echo "connect failed. Error: {$client->errCode}" . PHP_EOL;
            $client->close();
            usleep(100000);
            goto SCL;
        }
        $client->set(array(
            'open_length_check'     => true,
            'package_length_type'   => 'N',
            'package_length_offset' => 0, //第N个字节是包长度的值
            'package_body_offset'   => 4, //第几个字节开始计算长度
            'package_max_length'    => 1024 * 1024 * 20, //协议最大长度
            'socket_buffer_size'    => 1024 * 1024 * 200, //2M缓存区
        ));
        $data = pack('N', strlen($filecontent)) . $filecontent;
        $client->send($data);
    }
}

new SourceFileServer();