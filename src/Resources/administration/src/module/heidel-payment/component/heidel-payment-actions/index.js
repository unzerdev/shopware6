import { Component } from 'src/core/shopware';
import template from './heidel-payment-actions.html.twig';

Component.register('heidel-payment-actions', {
    template,

    props: {
        paymentResource: {
            type: Object,
            required: true
        },
    }
});
