<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace YaBand\WechatPay\Model\Config\Source\Order\Status;


/**
 * Order Status source model
 */
class Pending
{
    public function toOptionArray()
    {
        $statuses = [
            'pending' => 'pending'
        ];

        $options = [ [ 'value' => '', 'label' => __('-- Please Select --') ] ];
        foreach($statuses as $code => $label){
            $options[] = [ 'value' => $code, 'label' => $label ];
        }
        return $options;
    }
}
