import './component/register-webhook';
import './component/unzer-webhooks-modal';
import './component/unzer-entity-single-select-delivery-status';
import './component/unzer-payment-apple-pay-certificates';
import './component/unzer-payment-plugin-icon';

import './extension/sw-settings-index';
import './extension/sw-system-config';

import './page/unzer-payment-settings';

import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

const { Module } = Shopware;

let configuration = {
    type: 'plugin',
    name: 'UnzerPayment',
    title: 'unzer-payment-settings.module.title',
    description: 'unzer-payment-settings.module.description',
    version: '1.1.0',
    targetVersion: '1.1.0',

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
};

const version = Shopware.Context.app.config.version;
const match = version.match(/((\d+)\.?(\d+?)\.?(\d+)?\.?(\d*))-?([A-z]+?\d+)?/i);

if(match && parseInt(match[2]) === 6 && parseInt(match[3]) > 3) {
    configuration.settingsItem = [{
        name:   'unzer-payment-configuration',
        to:     'unzer.payment.configuration.settings',
        label:  'unzer-payment-settings.module.title',
        group:  'plugins',
        iconComponent: 'unzer-payment-plugin-icon',
        backgroundEnabled: false
    }]
}

Module.register('unzer-payment-configuration', configuration);
