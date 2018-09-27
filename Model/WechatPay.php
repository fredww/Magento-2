<?php

namespace YaBandPay\Payment\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use YaBandPay\Payment\Helper\General as YaBandWechatPayHelper;
use YaBandPay\Payment\Helper\General;

/**
 * Class WechatPay
 *
 * @package YaBandPay\Payment\Model
 * @description
 * @version 1.0.0
 */
class WechatPay extends AbstractMethod
{
    const CODE = 'wechatpay';

    protected $_code = self::CODE;
    /**
     * Enable Initialize
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    /**
     * Enable Gateway
     *
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * Enable Refund
     *
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * Enable Partial Refund
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    protected $_canUseCheckout = true;

    protected $_canCapture = true;

    /**
     * @var array
     */
    private $issuers = [];
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var YaBandWechatPayHelper
     */
    private $yaBandWechatPayHelper;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Mollie constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ObjectManagerInterface $objectManager
     * @param YaBandWechatPayHelper $yaBandWechatPayHelper
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Order $order
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ObjectManagerInterface $objectManager,
        YaBandWechatPayHelper $yaBandWechatPayHelper,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Order $order,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->objectManager = $objectManager;
        $this->yaBandWechatPayHelper = $yaBandWechatPayHelper;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $this->getInfoInstance();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $status = $this->yaBandWechatPayHelper->getStatusPending($order->getStoreId());
        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     * @throws \Exception
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function startTransaction(Order $order)
    {
        /*$yaOrderId = $order->getYaOrderId();
        if(!empty($yaOrderId)){

        }*/
        $orderPayUrl = $this->yaBandWechatPayHelper->getPayUrl($order);
        $message = __('Customer redirected to YaBandWechatPay, url: %1', $orderPayUrl);
        $status = $this->yaBandWechatPayHelper->getStatusPending($order->getStoreId());
        $order->addStatusToHistory($status, $message, false);
        $order->save();
        if(!empty($orderPayUrl)){
            return $orderPayUrl;
        }
        return $this->yaBandWechatPayHelper->getCheckoutUrl();
    }

    /**
     * Process Transaction (webhook / success)
     *
     * @param        $orderId
     * @param string $type
     *
     * @return array
     * @throws \Exception
     */
    public function processTransaction(array $orderInfo)
    {
        $order = $this->order->load($orderInfo['order_id']);
        if(empty($order)){
            $msg = [ 'error' => true, 'msg' => __('Order not found') ];
            $this->yaBandWechatPayHelper->addTolog('error', $msg);
            return $msg;
        }

        $storeId = $order->getStoreId();

        $this->yaBandWechatPayHelper->addTolog('notify', \json_encode($orderInfo, JSON_UNESCAPED_UNICODE));
        $status = $orderInfo['state'];

        if($status == General::PAY_PAID){
            if(!$order->getIsVirtual()){
                $defaultStatusProcessing = $this->yaBandWechatPayHelper->getStatusProcessing($storeId);
                if($defaultStatusProcessing && ($defaultStatusProcessing != $order->getStatus())){
                    $order->setStatus($defaultStatusProcessing)->save();
                }
            }

            $msg = [ 'success' => true, 'status' => 'paid', 'order_id' => $orderInfo['order_id'] ];
            $this->yaBandWechatPayHelper->addTolog('success', $msg);
            return $msg;
        }

        $msg = [ 'success' => false, 'status' => $status, 'order_id' => $orderInfo['order_id'] ];
        $this->yaBandWechatPayHelper->addTolog('success', $msg);
        return $msg;
    }
}
