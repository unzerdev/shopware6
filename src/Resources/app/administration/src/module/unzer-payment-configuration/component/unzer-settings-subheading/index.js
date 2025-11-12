import template from './unzer-settings-subheading.html.twig';
import './style.scss';

const { Component } = Shopware;

Component.register('unzer-settings-subheading', {
    template,
    computed: {
        label() {
            // get part after last dot
            const parts = this.$attrs.name.split('.');
            const key = parts[parts.length - 1];
            return this.$tc('unzer-payment-settings.subheadings.' + key);
        },
    },
});
