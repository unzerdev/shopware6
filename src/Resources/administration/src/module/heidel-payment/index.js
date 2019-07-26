import { Module } from 'src/core/shopware';

import './extension/sw-order';
import './page/heidel-payment-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('heidel-payment', {
    type: 'plugin',
    name: 'HeidelPayment',
    title: 'heidel-payment.general.title',
    description: 'heidel-payment.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                component: 'heidel-payment-detail',
                name: 'heidel-payment.payment.detail',
                isChildren: true,
                path: '/sw/order/heidelpayment/detail/:id'
            });
        }

        next(currentRoute);
    }
});
