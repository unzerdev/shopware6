const { Module } = Shopware;

import './extension/sw-plugin-list';

import './page/heidel-payment-settings';

import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

Module.register('heidel-payment-configuration', {
    type: 'plugin',
    name: 'HeidelPayment',
    title: 'heidel-payment-settings.module.title',
    description: 'heidel-payment-settings.module.description',
    version: '1.0.0',
    targetVersion: '1.0.0',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        settings: {
            component: 'heidel-payment-settings',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
    },
});
