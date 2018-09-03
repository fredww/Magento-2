<?php
/**
 * @project: YabanPay-Magento2
 * @description:
 * @user: persi
 * @email persi@sixsir.com
 * @date: 2018/9/1
 * @time: 13:49
 */

namespace YaBandPay\Payment\Controller;


use Magento\Framework\App\Action\Action;
use YaBandPay\Payment\Helper\General;
use YaBandPay\Payment\Model\Log;

abstract class Controller extends Action
{
    /**
     * @var \YaBandPay\Payment\Helper\General
     */
    protected $yaBandWechatPayHelper;
    /**
     * @var \YaBandPay\Payment\Model\WechatPay
     */
    protected $wechatPay;

    public function parseOrderInfo()
    {
        $result = \file_get_contents('php://input');
        if(!empty($result)){
            $result = \urldecode($result);
        }else{
            $resultData = $this->getRequest()->getParam('resultData');
            if(!empty($resultData)){
                $result = $resultData;
            }else{
                return null;
            }
        }
        if(\strpos($result, 'resultData=') !== false){
            $orderInfo = \substr($result, \strlen('resultData='));
        }else{
            $orderInfo = $result;
        }
        $orderInfo = General::verifySign($orderInfo, $this->yaBandWechatPayHelper->getApiToken());
        return $orderInfo;
    }
}