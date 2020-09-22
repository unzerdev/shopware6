import './component/register-webhook';

import './extension/sw-settings-index';

import './page/heidel-payment-settings';

import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

const { Module } = Shopware;

Module.register('heidel-payment-configuration', {
    type: 'plugin',
    name: 'HeidelPayment',
    title: 'heidel-payment-settings.module.title',
    description: 'heidel-payment-settings.module.description',
    version: '0.0.1',
    targetVersion: '0.0.1',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        settings: {
            component: 'heidel-payment-settings',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
});
