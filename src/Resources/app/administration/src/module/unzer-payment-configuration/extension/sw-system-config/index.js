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
    watch: {
        currentSalesChannelId() {
            this.getUnzerGooglePayGatewayMerchantId();
            this.$emit('sales-channel-changed', this.actualConfigData[this.currentSalesChannelId], this.currentSalesChannelId);
        },
    },
    computed: {
        unzerGooglePayGatewayMerchantId() {
            return this.readOnlyUnzerGooglePayGatewayMerchantId || '';
        }
    },
    methods: {
        async createdComponent() {
            await this.$super('createdComponent');
            this.getUnzerGooglePayGatewayMerchantId();
        },
        getUnzerGooglePayGatewayMerchantId() {
            if (this.domain === 'UnzerPayment6.settings') {
                this.UnzerPaymentConfigurationService.getGooglePayGatewayMerchantId(this.currentSalesChannelId).then((response) => {
                    this.readOnlyUnzerGooglePayGatewayMerchantId = response.gatewayMerchantId;
                })
                    .catch(() => {

                    });
            }
        }
    }
});
