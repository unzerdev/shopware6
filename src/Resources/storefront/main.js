import './styles/main.scss';

import HeidelpayBasePlugin from './script/plugin/heidelpay/heidelpay-base.plugin';
import HeidelpayCreditCardPlugin from './script/plugin/heidelpay/heidelpay-credit-card.plugin';

window.PluginManager.register('HeidelpayBase', HeidelpayBasePlugin, '[data-heidelpay-base]');
window.PluginManager.register('HeidelpayCreditCard', HeidelpayCreditCardPlugin, '[data-heidelpay-credit-card]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
