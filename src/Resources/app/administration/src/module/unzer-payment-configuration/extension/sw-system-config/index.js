const { Component } = Shopware;

Component.override('sw-system-config', {
    methods: {
        onSalesChannelChanged(salesChannelId) {
            this.$super('onSalesChannelChanged', salesChannelId);
            this.$emit('sales-channel-changed', this.actualConfigData[this.currentSalesChannelId], this.currentSalesChannelId);
        }
    }
});
