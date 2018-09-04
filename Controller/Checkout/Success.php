<?php
/**
 * @project    : YabanPay-Magento2
 * @description:
 * @user       : persi
 * @email persi@sixsir.com
 * @date       : 2018/9/1
 * @time       : 11:42
 */

namespace YaBandPay\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use YaBandPay\Payment\Controller\Controller;
use YaBandPay\Payment\Helper\General;
use YaBandPay\Payment\Helper\General as YaBandWechatPayHelper;
use YaBandPay\Payment\Model\WechatPay;

class Success extends Controller
{
    /**
     * Redirect constructor.
     *
     * @param Context               $context
     * @param Session               $checkoutSession
     * @param PaymentHelper         $paymentHelper
     * @param YaBandWechatPayHelper $yaBandWechatPayHelper
     */
    public function __construct(
        Context $context,
        WechatPay $wechatPay,
        PaymentHelper $paymentHelper,
        YaBandWechatPayHelper $yaBandWechatPayHelper
    ) {
        $this->resultFactory = $context->getResultFactory();
        $this->paymentHelper = $paymentHelper;
        $this->yaBandWechatPayHelper = $yaBandWechatPayHelper;
        $this->wechatPay = $wechatPay;
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Mollie after placing order
     */
    public function execute()
    {
        try {
            $orderInfo = $this->parseOrderInfo();
            if ($orderInfo['status'] === false) {
                $this->messageManager->addErrorMessage($orderInfo['msg']);
                $this->_redirect('checkout/cart');
            } else {
                if ($orderInfo['status'] === true
                    && $orderInfo['order_info']['state'] === General::PAY_PAID
                ) {
                    $orderInfo = $orderInfo['order_info'];
                    $this->wechatPay->processTransaction($orderInfo);
                    $this->_redirect(
                        'checkout/onepage/success?utm_nooverride=1'
                    );
                } else {
                    $this->_redirect('checkout/onepage/error?utm_nooverride=1');
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e, __($e->getMessage())
            );
            $this->yaBandWechatPayHelper->addTolog('error', $e->getMessage());
            $this->_redirect('checkout/cart');
        }
    }
}
