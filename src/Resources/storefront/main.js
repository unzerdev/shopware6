import HeidelpayBasePlugin from './script/plugin/heidelpay/heidelpay-base.plugin';
import HeidelpayCreditCardPlugin from './script/plugin/heidelpay/heidelpay-credit-card.plugin';
import HeidelpayInvoicePlugin from './script/plugin/heidelpay/heidelpay-invoice.plugin';
import HeidelpayInvoiceGuaranteedPlugin from './script/plugin/heidelpay/heidelpay-invoice-guaranteed.plugin';
import HeidelpayInvoiceFactoringPlugin from './script/plugin/heidelpay/heidelpay-invoice-factoring.plugin';
import HeidelpayEpsPlugin from './script/plugin/heidelpay/heidelpay-eps.plugin';
import HeidelpayIdealPlugin from './script/plugin/heidelpay/heidelpay-ideal.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('HeidelpayBase', HeidelpayBasePlugin, '[data-heidelpay-base]');
PluginManager.register('HeidelpayCreditCard', HeidelpayCreditCardPlugin, '[data-heidelpay-credit-card]');
PluginManager.register('HeidelpayInvoice', HeidelpayInvoicePlugin, '[data-heidelpay-invoice]');
PluginManager.register('HeidelpayInvoiceGuaranteed', HeidelpayInvoiceGuaranteedPlugin, '[data-heidelpay-invoice-guaranteed]');
PluginManager.register('HeidelpayInvoiceFactoring', HeidelpayInvoiceFactoringPlugin, '[data-heidelpay-invoice-factoring]');
PluginManager.register('HeidelpayEps', HeidelpayEpsPlugin, '[data-heidelpay-eps]');
PluginManager.register('HeidelpayIdeal', HeidelpayIdealPlugin, '[data-heidelpay-ideal]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
