import template from './unzer-payment-plugin-icon.html.twig';

const { Component } = Shopware;

Component.register('unzer-payment-plugin-icon', {
    template,
    computed:{
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    }
});
