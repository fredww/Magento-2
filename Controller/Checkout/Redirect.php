<?php
/**
 * @project: YabanPay-Magento2
 * @description:
 * @user: persi
 * @email persi@sixsir.com
 * @date: 2018/9/1
 * @time: 11:42
 */

namespace YaBandPay\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use YaBandPay\Payment\Controller\Controller;
use YaBandPay\Payment\Helper\General as YaBandWechatPayHelper;
use YaBandPay\Payment\Model\Log;

class Redirect extends Controller
{
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    /**
     * @var YaBandWechatPayHelper
     */
    protected $yaBandWechatPayHelper;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param PageFactory $resultPageFactory
     * @param PaymentHelper $paymentHelper
     * @param YaBandWechatPayHelper $yaBandWechatPayHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PageFactory $resultPageFactory,
        PaymentHelper $paymentHelper,
        YaBandWechatPayHelper $yaBandWechatPayHelper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentHelper = $paymentHelper;
        $this->yaBandWechatPayHelper = $yaBandWechatPayHelper;
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Mollie after placing order
     */
    public function execute()
    {
        try{
            $order = $this->checkoutSession->getLastRealOrder();
            if(!$order){
                $msg = __('Order not found.');
                $this->yaBandWechatPayHelper->addTolog('error', $msg);
                $this->_redirect('checkout/cart');
                return;
            }
            $payment = $order->getPayment();
            if(!isset($payment)){
                $this->_redirect('checkout/cart');
                return;
            }
            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->paymentHelper->getMethodInstance($method);
            if($methodInstance instanceof \YaBandPay\Payment\Model\WechatPay){
                $redirectUrl = $methodInstance->startTransaction($order);
                $this->yaBandWechatPayHelper->addTolog('request', $redirectUrl);
                $this->getResponse()->setRedirect($redirectUrl);
            }else{
                $msg = __('Paymentmethod not found.');
                $this->messageManager->addErrorMessage($msg);
                $this->yaBandWechatPayHelper->addTolog('error', $msg);
                $this->checkoutSession->restoreQuote();
                $this->_redirect('checkout/cart');
            }
        }catch(\Exception $e){
            $this->messageManager->addExceptionMessage($e, __($e->getMessage()));
            $this->yaBandWechatPayHelper->addTolog('error', $e->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
