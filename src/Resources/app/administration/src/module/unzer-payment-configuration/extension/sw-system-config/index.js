const { Component } = Shopware;

import template from './sw-system-config.html.twig';

Component.override('sw-system-config', {
    template,
    methods: {
        onSalesChannelChanged(salesChannelId) {
            this.$super('onSalesChannelChanged', salesChannelId);
            this.$emit('sales-channel-changed', this.actualConfigData[this.currentSalesChannelId], this.currentSalesChannelId);
        }
    }
});
