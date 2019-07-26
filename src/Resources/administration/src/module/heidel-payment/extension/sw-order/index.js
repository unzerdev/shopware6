import { Component } from 'src/core/shopware';
import template from './sw-order.html.twig';

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isHeidelpayPayment: true
        };
    },

    computed: {
        showTabs() {
            return true; // TODO remove with PT-10455
        },
    },

    created() {
        // ToDo with NEXT-3911: Remove this Quickfix
        this.$router.push({ name: 'sw.order.detail', params: { id: this.orderId } });
    },
});
