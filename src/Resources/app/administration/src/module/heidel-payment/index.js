import './component/heidel-payment-actions';
import './component/heidel-payment-detail';
import './component/heidel-payment-history';
import './component/heidel-payment-metadata';
import './component/heidel-payment-basket';
import './extension/sw-order-detail';
import './extension/sw-order-list';
import './extension/sw-order-create-details-footer';
import './page/heidel-payment-tab';

import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

const { Module } = Shopware;

Module.register('heidel-payment', {
    type: 'plugin',
    name: 'HeidelPayment',
    title: 'heidel-payment.general.title',
    description: 'heidel-payment.general.descriptionTextModule',
    version: '0.0.1',
    targetVersion: '0.0.1',

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
