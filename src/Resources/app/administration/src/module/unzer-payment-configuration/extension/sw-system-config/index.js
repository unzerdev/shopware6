const {Component} = Shopware;

import template from './sw-system-config.html.twig';

Component.override('sw-system-config', {
    template,
    inject: [
        'UnzerPaymentConfigurationService'
    ],
    data() {
        return {
            readOnlyUnzerGooglePayGatewayMerchantId: {}
        };
    },
    methods: {
        onSalesChannelChanged(salesChannelId) {
            this.$super('onSalesChannelChanged', salesChannelId);
            this.$emit('sales-channel-changed', this.actualConfigData[this.currentSalesChannelId], this.currentSalesChannelId);
        },
        readAll() {
            if (this.domain === 'UnzerPayment6.settings') {
                this.UnzerPaymentConfigurationService.getGooglePayGatewayMerchantId(this.currentSalesChannelId).then((response) => {
                    this.readOnlyUnzerGooglePayGatewayMerchantId = response.gatewayMerchantId;
                })
                    .catch(() => {

                    });
            }
            return this.$super('readAll');
        },
        getUnzerGooglePayGatewayMerchantId() {
            return this.readOnlyUnzerGooglePayGatewayMerchantId || '';
        }
    }
});
