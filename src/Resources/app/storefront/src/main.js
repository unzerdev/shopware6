import HeidelpayBasePlugin from './heidelpay/heidelpay-base.plugin';
import HeidelpayCreditCardPlugin from './heidelpay/heidelpay-credit-card.plugin';
import HeidelpayInvoicePlugin from './heidelpay/heidelpay-invoice.plugin';
import HeidelpayInvoiceGuaranteedPlugin from './heidelpay/heidelpay-invoice-guaranteed.plugin';
import HeidelpayInvoiceFactoringPlugin from './heidelpay/heidelpay-invoice-factoring.plugin';
import HeidelpayEpsPlugin from './heidelpay/heidelpay-eps.plugin';
import HeidelpayIdealPlugin from './heidelpay/heidelpay-ideal.plugin';
import HeidelpaySepaDirectDebitPlugin from './heidelpay/heidelpay-sepa-direct-debit.plugin';
import HeidelpaySepaDirectDebitGuaranteedPlugin from './heidelpay/heidelpay-sepa-direct-debit-guaranteed.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('HeidelpayBase', HeidelpayBasePlugin, '[data-heidelpay-base]');
PluginManager.register('HeidelpayCreditCard', HeidelpayCreditCardPlugin, '[data-heidelpay-credit-card]');
PluginManager.register('HeidelpayInvoice', HeidelpayInvoicePlugin, '[data-heidelpay-invoice]');
PluginManager.register('HeidelpayInvoiceGuaranteed', HeidelpayInvoiceGuaranteedPlugin, '[data-heidelpay-invoice-guaranteed]');
PluginManager.register('HeidelpayInvoiceFactoring', HeidelpayInvoiceFactoringPlugin, '[data-heidelpay-invoice-factoring]');
PluginManager.register('HeidelpayEps', HeidelpayEpsPlugin, '[data-heidelpay-eps]');
PluginManager.register('HeidelpayIdeal', HeidelpayIdealPlugin, '[data-heidelpay-ideal]');
PluginManager.register('HeidelpaySepaDirectDebit', HeidelpaySepaDirectDebitPlugin, '[data-heidelpay-sepa-direct-debit]');
PluginManager.register('HeidelpaySepaDirectDebitGuaranteed', HeidelpaySepaDirectDebitGuaranteedPlugin, '[data-heidelpay-sepa-direct-debit-guaranteed]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
