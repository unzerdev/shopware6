const { Component } = Shopware;

Component.override('sw-system-config', {
    watch: {
        currentSalesChannelId() {
            this.$emit(
                'sales-channel-changed',
                this.actualConfigData[this.currentSalesChannelId],
                this.currentSalesChannelId
            );
        },
    },
});
