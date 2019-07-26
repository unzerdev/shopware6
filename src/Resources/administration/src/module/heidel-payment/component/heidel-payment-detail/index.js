import { Component } from 'src/core/shopware';
import template from './heidel-payment-detail.html.twig';

Component.register('heidel-payment-detail', {
    template,

    props: {
        paymentResource: {
            type: Object,
            required: true
        },
    },
});
