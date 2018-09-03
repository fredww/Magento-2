/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        // 'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (Component, url) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'YaBand_WechatPay/payment/wechatpay'
            },
            getMethodImage: function () {
                return checkoutConfig.image[this.item.method];
            },
            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            afterPlaceOrder: function () {
                console.log("afterPlaceOrder");
                window.location.href = url.build('yabandwechatpay/checkout/redirect/');
            }
        });
    }
);
