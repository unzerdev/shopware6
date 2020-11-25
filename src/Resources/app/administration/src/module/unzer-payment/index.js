import './component/unzer-payment-actions';
import './component/unzer-payment-detail';
import './component/unzer-payment-history';
import './component/unzer-payment-metadata';
import './component/unzer-payment-basket';
import './extension/sw-order-create-details-footer';
import './extension/sw-order-detail';
import './extension/sw-order-list';
import './page/unzer-payment-tab';

import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

const { Module } = Shopware;

Module.register('unzer-payment', {
    type: 'plugin',
    name: 'UnzerPayment',
    title: 'unzer-payment.general.title',
    description: 'unzer-payment.general.descriptionTextModule',
    version: '0.0.1',
    targetVersion: '0.0.1',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                component: 'unzer-payment-tab',
                name: 'unzer-payment.payment.detail',
                path: '/sw/order/detail/:id/unzer-payment',
                isChildren: true,
                meta: {
                    parentPath: 'sw.order.index'
                }
            });
        }

        next(currentRoute);
    }
});
