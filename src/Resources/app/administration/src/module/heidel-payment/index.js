const { Module } = Shopware;

import './component/heidel-payment-actions';
import './component/heidel-payment-detail';
import './component/heidel-payment-history';
import './component/heidel-payment-metadata';
import './component/heidel-payment-basket';
import './extension/sw-order';
import './page/heidel-payment-tab';

import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

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
                component: 'heidel-payment-tab',
                name: 'heidel-payment.payment.detail',
                isChildren: true,
                path: '/sw/order/heidelpayment/detail/:id'
            });
        }

        next(currentRoute);
    }
});
