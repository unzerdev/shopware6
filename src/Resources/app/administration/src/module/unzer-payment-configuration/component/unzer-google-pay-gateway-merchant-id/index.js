const {Component} = Shopware;

import template from './unzer-google-pay-gateway-merchant-id.html.twig';

Component.register('unzer-google-pay-gateway-merchant-id', {
    template,
    inject: ['UnzerPaymentConfigurationService'],
    data() {
        return {
            readOnlyUnzerGooglePayGatewayMerchantId: '',
        };
    },
    props: {
        currentSalesChannelId: {
            type: String,
            required: true,
        },
    },
    watch: {
        currentSalesChannelId() {
            this.getUnzerGooglePayGatewayMerchantId();
        },
    },
    created() {
        this.getUnzerGooglePayGatewayMerchantId();
    },
    methods: {

        getUnzerGooglePayGatewayMerchantId() {
            console.log('get', this.currentSalesChannelId);
            this.UnzerPaymentConfigurationService.getGooglePayGatewayMerchantId(
                this.currentSalesChannelId
            )
                .then((response) => {
                    this.readOnlyUnzerGooglePayGatewayMerchantId = response.gatewayMerchantId;
                })
                .catch(() => {
                });

        },
    },
});
