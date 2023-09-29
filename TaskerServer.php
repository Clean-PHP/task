<?php
/*******************************************************************************
 * Copyright (c) 2022. CleanPHP. All Rights Reserved.
 ******************************************************************************/

namespace library\task;

use cleanphp\App;
use cleanphp\base\Variables;
use cleanphp\cache\Cache;
use cleanphp\file\Log;


/**
 * Class Server
 * @package extend\net_ankio_tasker\core
 * Date: 2020/12/31 09:57
 * Author: ankio
 * Description:Tasker服务
 */
class TaskerServer
{

    /**
     * 启动任务扫描服务
     * @return void
     */
    public static function start(): void
    {

        $file = Variables::getCachePath("servers", DS, "tasker_server_" . md5("tasker_server"));
        if (!file_exists($file) || filemtime($file) + 30 < time()) {//没有锁定，请求保持锁定
            App::$debug && Log::record("Tasker", "定时任务进程未锁定，下发任务");
            //该项只需执行一次，20秒内不重复
            Cache::init(15, Variables::getCachePath("servers", DS))->set("tasker_server", getmypid());//更新锁定时间
            go(function () {
                App::$debug && Log::record("Tasker", "定时任务进程启动");
                do {
                    Cache::init(3600, Variables::getCachePath("servers", DS))->set("tasker_server", getmypid());//更新锁定时间
                    TaskerManager::run();
                    sleep(10);
                    $pid = Cache::init(3600, Variables::getCachePath("servers", DS))->get("tasker_server");
                    if ($pid !== getmypid()) {
                        App::$debug && Log::record("Tasker", "定时任务进程（{$pid}）发生变化，当前进程结束");
                        break;
                    }
                    App::$debug && Log::record("Tasker", "$pid 心跳执行中...");
                } while (true);
            }, 0);

        } else {
            App::$debug && Log::record("Tasker", "定时任务进程已锁定，不处理定时任务");
        }
    }

    //停止任务
    public static function stop()
    {
        Cache::init(15, Variables::getCachePath("servers", DS))->del("tasker_server");
    }

}