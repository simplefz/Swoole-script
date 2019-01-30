# Swoole-script

+ 基于swoole 执行多进程调度脚本任务
+ http 请求带参调用脚本
+ 调度任务为异步阻塞状态
+ 启动方式php SwooleService.php
+ 状态查看 ：pstree -ap | grep SwooleService.php 
+ Swoole HTTP SERVER 编写代码 以及 脚本任务不可出现 exit die 等强制退出等动作。
+ 执行脚本均以$argv方式接受参数

