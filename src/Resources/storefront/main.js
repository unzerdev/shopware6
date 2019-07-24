import HeidelpayBasePlugin from './script/plugin/heidelpay/heidelpay-base.plugin';
import HeidelpayCreditCardPlugin from './script/plugin/heidelpay/heidelpay-credit-card.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('HeidelpayBase', HeidelpayBasePlugin, '[data-heidelpay-base]');
PluginManager.register('HeidelpayCreditCard', HeidelpayCreditCardPlugin, '[data-heidelpay-credit-card]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
