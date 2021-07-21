/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'EService_Payment/js/action/set-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/error-processor'
    ],
    function(ko, $, Component, setPaymentMethodAction, quote,
             additionalValidators, fullScreenLoader, errorProcessor) {
        'use strict';
        var paymentMethod = ko.observable(null);

        return Component.extend({
            self: this,
            defaults: {
                template: 'EService_Payment/payment/payment-form'
            },
            initialize: function() {
                this._super();
            },
            /** Redirect mode*/
            continueToPayment: function() {
                if (this.validate() && additionalValidators.validate()) {
                    if (true || window.checkoutConfig.payment[quote.paymentMethod().method].displayMode === 'redirect') {
                        setPaymentMethodAction()
                            .done(
                                function() {
                                    $.mage.redirect(window.checkoutConfig.payment[quote.paymentMethod().method].redirectUrl);
                                }
                            ).fail(
                            function(response) {
                                errorProcessor.process(response);
                                fullScreenLoader.stopLoader();
                            }
                        );
                    }
                    return false;
                }
            },
            validate: function() {
                return true;
            }
        });
    }
);