<?php
/**
 * @project    : YabanPay-Magento2
 * @description:
 * @user       : persi
 * @email      :persi@sixsir.com
 * @date       : 2018/8/31
 * @time       : 20:55
 */

namespace YaBandPay\Payment\Helper;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use YaBandPay\Payment\Logger\Logger;
use YaBandPay\Payment\Model\WechatPay;

/**
 * Class General
 *
 * @package YaBandPay\Payment\Helper
 * @description
 * @version 1.0.0
 */
class General extends AbstractHelper
{
    const MODULE_CODE = WechatPay::CODE;
    const WECHATPAY_USERNAME = 'payment/' . self::MODULE_CODE . '/username';
    const WECHATPAY_PASSWORD = 'payment/' . self::MODULE_CODE . '/password';
    const WECHATPAY_TOKEN = 'payment/' . self::MODULE_CODE . '/token';
    const WECHATPAY_CURRENCY = 'payment/' . self::MODULE_CODE . '/currency';
    const WECHATPAY_DEBUG = 'payment/' . self::MODULE_CODE . '/debug';

    const WECHATPAY_STATUS_PENDING
        = 'payment/' . self::MODULE_CODE . '/pending_status';
    const WECHATPAY_STATUS_PROCESSING
        = 'payment/' . self::MODULE_CODE . '/processing_status';

    const PAY_PENDING = 'pending';

    const PAY_PROCESSING = 'processing';

    const PAY_PAID = 'paid';

    const PAY_CANCELLED = 'canceled';

    const PAY_FAILED = 'failed';

    const PAY_REFUNDED = 'refunded';

    const PAY_EXPIRED = 'expired';

    const PAY_COMPLETED = 'completed';
    /**
     * @var ProductMetadataInterface
     */
    private $metadata;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $resourceConfig;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;
    /**
     * @var ModuleListInterface
     */
    private $moduleList;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var
     */
    private $apiUsername;
    /**
     * @var
     */
    private $apiPassword;
    /**
     * @var
     */
    private $apiToken;
    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * General constructor.
     *
     * @param Context                  $context
     * @param StoreManagerInterface    $storeManager
     * @param Config                   $resourceConfig
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $metadata
     * @param Resolver                 $resolver
     * @param Logger                   $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $resourceConfig,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        Resolver $resolver,
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->resolver = $resolver;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Get admin value by path and storeId
     *
     * @param     $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStoreConfig($path, $storeId = 0)
    {
        return $this->scopeConfig->getValue(
            $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getApiUserName($storeId = null)
    {
        $apiUserName = trim(
            $this->getStoreConfig(self::WECHATPAY_USERNAME, $storeId)
        );
        if (empty($apiUserName)) {
            $this->addTolog('error', 'YaBand WechatPay API Username not set');
        }
        $this->apiUsername = $apiUserName;
        return $this->apiUsername;
    }

    public function getApiPassword($storeId = null)
    {
        $apiPassword = trim(
            $this->getStoreConfig(self::WECHATPAY_PASSWORD, $storeId)
        );
        if (empty($apiPassword)) {
            $this->addTolog('error', 'YaBand WechatPay API Username not set');
        }
        $this->apiPassword = $apiPassword;
        return $this->apiPassword;
    }

    public function getApiToken($storeId = null)
    {
        $apiToken = trim(
            $this->getStoreConfig(self::WECHATPAY_TOKEN, $storeId)
        );
        if (empty($apiToken)) {
            $this->addTolog('error', 'YaBand WechatPay API Username not set');
        }
        $this->apiToken = $apiToken;
        return $this->apiToken;
    }

    public function getPayCurrency($storeId)
    {
        return $this->getStoreConfig(self::WECHATPAY_CURRENCY, $storeId);
    }

    /**
     * Write to log
     *
     * @param $type
     * @param $data
     */
    public function addTolog($type, $data)
    {
        $debug = $this->getStoreConfig(self::WECHATPAY_DEBUG);
        if ($debug) {
            if ($type == 'error') {
                $this->logger->addErrorLog($type, $data);
            } else {
                $this->logger->addInfoLog($type, $data);
            }
        }
    }


    public function getPayUrl(Order $order)
    {
        $orderId = $order->getId();
        return $this->generateOrderPayUrl(
            $orderId, $this->getOrderAmountByOrder($order),
            $this->getPayCurrency($order->getStoreId()),
            $order->getIncrementId(), $this->getApiUserName(),
            $this->getApiPassword(), $this->getRedirectUrl(),
            $this->getNotifyUrl()
        );
    }

    /**
     * Redirect Url Builder /w OrderId & UTM No Override
     *
     * @param $orderId
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->urlBuilder->getUrl('yabandwechatpay/checkout/success/');
    }

    /**
     * Webhook Url Builder
     *
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->urlBuilder->getUrl('yabandwechatpay/checkout/notify/');
    }

    /**
     * Checkout Url Builder
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->urlBuilder->getUrl('checkout/cart');
    }

    /**
     * Restart Url Builder
     *
     * @return string
     */
    public function getRestartUrl()
    {
        return $this->urlBuilder->getUrl('mollie/checkout/restart/');
    }

    /**
     * Selected processing status
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusProcessing($storeId = 0)
    {
        return self::PAY_PROCESSING;
    }

    /**
     * Selected pending (payment) status
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusPending($storeId = 0)
    {
        return $this->getStoreConfig(self::WECHATPAY_STATUS_PENDING, $storeId);
    }

    /**
     * getOrderAmountByOrder
     *
     * @description
     * @version 1.0.0
     *
     * @param $order
     *
     * @return mixed
     */
    public function getOrderAmountByOrder(Order $order)
    {
        $orderAmount = $order->getBaseGrandTotal();
        return $orderAmount;
    }

    private function generateOrderPayUrl($orderId, $totalAmount, $currency,
        $description, $username, $password, $redirectUrl, $notifyUrl
    ) {
        $createPayUrl = 'https://api.yabandpay.com/getPayurl.php';
        $orderInfo = array(
            "pay_method"   => "wechatPay",
            "order_id"     => $orderId,
            "amount"       => $totalAmount,
            "currency"     => $currency,
            "description"  => $description,
            "user"         => $username,
            "password"     => $password,
            "redirect_url" => $redirectUrl,
            "notify_url"   => $notifyUrl
        );
        return $this->requestPost($createPayUrl, \json_encode($orderInfo));
    }

    private function queryOrderState($orderId)
    {
        $url = 'https://api.yabandpay.com/queryOrderState.php';
        return self::requestPost($url, array('ya_order_id' => $orderId));
    }

    private function requestPost($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: text/plain']);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public static function verifySign($data, $token)
    {
        $data = \urldecode($data);
        if (empty($data) || \strpos($data, '.') === false) {
            return array(
                'status'     => false,
                'order_info' => null,
                'sign'       => null,
                'msg'        => 'dot not found'
            );
        }
        $dot_len = \strpos($data, '.');
        $sign = \substr($data, 0, $dot_len);
        $order_json = \substr($data, $dot_len + 1, \mb_strlen($data));
        $new_sign = \hash_hmac('sha256', $order_json, $token);
        if ($new_sign !== $sign) {
            return array(
                'status'     => false,
                'order_info' => null,
                'sign'       => null,
                'msg'        => 'sign error'
            );
        }
        return array(
            'status'     => true,
            'order_info' => \json_decode($order_json, true),
            'sign'       => $sign
        );
    }
}
