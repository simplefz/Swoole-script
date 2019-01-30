<?php
    /**
     * Created by PhpStorm.
     * User: fanpengjie
     * Date: 18-10-11
     * Time: 上午9:51
     */

    namespace think;


    use app\script\WorkEvent;

    class SwooleService
    {
        const DIR = __DIR__;

        public $config = [
            'worker_num'         => 4,//启用1个进程
            'log_file'           => '/home/xxxxxx/swoole.log',
            'daemonize'          => 0,//1开启守护进程
            'task_worker_num'    => 4,// 投递任务进程
            'max_connection'     => 50,
        ];

        public function httpService()
        {
            $http = new \swoole_http_server('0.0.0.0', 9601);

            $http->set(
                $this->config
            );

            $http->on(
                'WorkerStart', function ($service, $workerId) {
                include(static::DIR . '/application/script/WorkEvent.php');
            }
            );

            $http->on(
                'shutdown', function ($service) {

            }
            );
            $http->on(
                'WorkerError', function ($service, $worker_id, $worker_pid, $exit_code) {
                $service->close($worker_pid,true);
            }
            );

            $http->on(
                'request', function ($request, $response) use ($http) {
                WorkEvent::onRequest($request, $response, $http);
            }
            );

            $http->on(
                'task', function ($service, $taskId, $fromId, $data) {

                WorkEvent::onTask($service, $taskId, $fromId, $data);
            }
            );

            $http->on(
                'finish', function ($service, $taskId, $data) {
                WorkEvent::onFinish($service, $taskId, $data);
            }
            );


            $http->start();
        }
    }


    $service = new SwooleService();
    $service->httpService();