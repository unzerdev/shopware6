import './component/register-webhook';

import './extension/sw-settings-index';

import './page/unzer-payment-settings';

import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

const { Module } = Shopware;

Module.register('unzer-payment-configuration', {
    type: 'plugin',
    name: 'UnzerPayment',
    title: 'unzer-payment-settings.module.title',
    description: 'unzer-payment-settings.module.description',
    version: '0.0.1',
    targetVersion: '0.0.1',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        settings: {
            component: 'unzer-payment-settings',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
});
