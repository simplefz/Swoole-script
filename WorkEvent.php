<?php
    /**
     * Created by PhpStorm.
     * User: fanpengjie
     * Date: 18-10-11
     * Time: 上午11:09
     */

    namespace app\script;


    use think\Exception;

    class WorkEvent
    {
        public static $_response;
        public static $_dataCount;
        public static $_taskSuccess = [];
        public static $_taskFailed  = [];

        /**
         * 处理request请求
         * @param $request
         * @param $response
         * @param $http
         */
        public static function onRequest($request, $response, $http)
        {
            $response->detach();
            $response = \Swoole\Http\Response::create(strval($response->fd));
            self::$_response = $response;
            switch ($request->server['request_uri']) {
                case '/api/swoole/worker':
                    $data = $request->get['data'];
                    $data = json_decode(base64_decode($data), true);
                    if (!empty($data)) {
                        self::$_dataCount = count($data);
                        foreach ($data as $key => $vaule) {
                            $http->task($vaule);
                        }
                    } else {
                        self::$_response->end(json_encode(['status' => 70001, 'message' => '参数错误!']));
                    }

                    break;
                case '/api/swoole/image':
                    $data = $request->get['data'];
                    $data = json_decode(base64_decode($data), true);
                    if (!empty($data)) {
                        self::$_dataCount = $http->setting['task_worker_num'];
                        foreach ($data as $key => $vaule){
                            for ($i = 1; $i <= self::$_dataCount; $i++) {
                                $http->task($vaule);
                            }
                        }

                    } else {
                        self::$_response->end(json_encode(['status' => 70001, 'message' => '参数错误!']));
                    }
                    break;
                default :
                    self::$_response->end(json_encode(['status' => 70001, 'message' => '地址不存在!']));
            }


        }

        /**
         * 处理同步任务
         * @param $service
         * @param $taskId
         * @param $fromId
         * @param $data
         */
        public static function onTask($service, $taskId, $fromId, $data)
        {
            try {
                exec(__DIR__ . "/../../" . $data['name'] . ' ' . $data['project_id'] . ' ' . $data['grade'] . ' ' . $data['subject_id'], $output);
                if ($output == 1) {
                    $service->finish(json_encode(['type' => $data['type'], 'name' => $data['name'], 'status' => array_shift($output)]));
                } else {
                    $service->finish(json_encode(['type' => $data['type'], 'name' => $data['name'], 'status' => array_shift($output)]));
                }
            } catch (Exception $exception) {
                $service->finish(json_encode(['type' => $data['type'], 'status' => $exception->getMessage()]));
            }
        }

        /**
         * 接受异步任务返回值
         * @param $service
         * @param $taskId
         * @param $data
         */
        public static function onFinish($service, $taskId, $data)
        {
            $data = json_decode($data, true);
            if ($data['status'] == 1) {
                self::$_taskSuccess[] = $data;
            } else {
                self::$_taskFailed[] = $data;
            }

            echo "AsyncTask[$taskId] Finish: {$data['type']}" . PHP_EOL;
            if ((count(self::$_taskSuccess) + count(self::$_taskFailed)) == self::$_dataCount) {

                $dataCount = self::$_dataCount;
                $success = self::$_taskSuccess;
                $failed = self::$_taskFailed;
                self::$_taskSuccess = [];
                self::$_taskFailed = [];
                self::$_dataCount = 0;
                self::$_response->end(json_encode(['status' => 70001, 'count' => $dataCount, 'success' => $success, 'failed' => $failed]));

            }
        }


        public static function onShutDown()
        {

        }
    }