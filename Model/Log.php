<?php
/**
 * @project: YabanPay-Magento2
 * @description:
 * @user: persi
 * @email persi@sixsir.com
 * @date: 2018/8/29
 * @time: 23:28
 */

namespace YaBand\WechatPay\Model;

class Log
{
    public static function message($message)
    {
        \file_put_contents(__DIR__ . '/' . \date('dmY') . '.debug.log', \var_export($message, true) . PHP_EOL, \FILE_APPEND);
    }
}