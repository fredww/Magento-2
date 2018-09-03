<?php
/**
 * @project: YabanPay-Magento2
 * @description:
 * @user: persi
 * @email persi@sixsir.com
 * @date: 2018/9/1
 * @time: 11:42
 */

namespace YaBand\WechatPay\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use YaBand\WechatPay\Controller\Controller;
use YaBand\WechatPay\Helper\General as YaBandWechatPayHelper;
use YaBand\WechatPay\Model\Log;
use YaBand\WechatPay\Model\WechatPay;
use function var_export;

class Notify extends Controller
{
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
        PageFactory $resultPageFactory,
        PaymentHelper $paymentHelper,
        WechatPay $wechatPay,
        YaBandWechatPayHelper $yaBandWechatPayHelper
    )
    {
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
        try{
            $orderInfo = $this->parseOrderInfo();
            if($orderInfo['status'] === true){
                $orderInfo = $orderInfo['order_info'];
                $this->wechatPay->processTransaction($orderInfo);
            }
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents('OK', true);
            return $result;
        }catch(\Exception $e){
            $this->messageManager->addExceptionMessage($e, __($e->getMessage()));
            $this->yaBandWechatPayHelper->addTolog('error', $e->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
