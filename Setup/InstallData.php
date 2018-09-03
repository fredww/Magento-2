<?php

namespace YaBandPay\Payment\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class InstallData implements InstallDataInterface
{

    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * InstallData constructor.
     *
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    )
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create([ 'setup' => $setup ]);

        /**
         * Add 'mollie_transaction_id' attributes for order
         */
        $salesSetup->addAttribute('order', 'wechat_transaction_id', array( 'type' => 'varchar', 'visible' => false, 'required' => false ));
        $salesSetup->addAttribute('order', 'ya_order_id', array( 'type' => 'varchar', 'visible' => false, 'required' => false ));
    }
}
