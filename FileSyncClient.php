<?php
class FileSyncClient
{
    private $serv;
    
    public function __construct($port)
    {
        date_default_timezone_set('PRC');
        $this->serv = new \swoole_server('0.0.0.0', $port);
        $this->serv->set(array(
            'worker_num'       => 8,
            'daemonize'        => true,
            'max_request'      => 10000,
            'dispatch_mode'    => 1,
            'debug_mode'       => 1,
            'task_worker_num'  => 10,
            // 'log_file' => '/tmp/swoole.log',
            "socketbuffersize" => 200 * 1024 * 1024,
            'open_length_check' => true,
            'package_max_length' => 20 * 1024 * 1024,
            'package_length_type' => 'N',
            'package_length_offset' => 0,
            'package_body_offset' => 4,

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
        // echo $str . PHP_EOL;
        $serv->task($str); //投递task任务
        $serv->send($fd, $str ? true : false);
    }

    public function onTask($serv, $task_id, $from_id, $param)
    {
        echo "onTask..." . $task_id . PHP_EOL;
        $this->dox($param);
        return $task_id;
    }

    public function dox($data)
    {
        $data = substr($data, 4);

        preg_match('/<-filename-(.*?)-filename->/', $data, $match);
        if ($match['1']) {
            $filename = $match['1'];
            echo $filename . PHP_EOL;
            $content = str_replace("<-filename-{$filename}-filename->", '', $data);
            //空文件表示 源文件被删除
            if (empty($content)) {
                unlink($filename);
                return;
            }
            $path    = explode('/', $filename);
            array_pop($path);
            $dirPath = implode('/', $path);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0777, true); //先创建文件夹，并设置权限
            }
            file_put_contents($filename, $content);
        }

    }

    public function onFinish($serv, $task_id, $param)
    {
        echo "onFinish==>" . $task_id . PHP_EOL;
    }

}
