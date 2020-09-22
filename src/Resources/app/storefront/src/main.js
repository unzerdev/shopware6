import UnzerPaymentBasePlugin from './unzer/unzer-base.plugin';
import UnzerPaymentCreditCardPlugin from './unzer/unzer-credit-card.plugin';
import UnzerPaymentInvoicePlugin from './unzer/unzer-invoice.plugin';
import UnzerPaymentInvoiceGuaranteedPlugin from './unzer/unzer-invoice-guaranteed.plugin';
import UnzerPaymentInvoiceFactoringPlugin from './unzer/unzer-invoice-factoring.plugin';
import UnzerPaymentEpsPlugin from './unzer/unzer-eps.plugin';
import UnzerPaymentIdealPlugin from './unzer/unzer-ideal.plugin';
import UnzerPaymentSepaDirectDebitPlugin from './unzer/unzer-sepa-direct-debit.plugin';
import UnzerPaymentSepaDirectDebitGuaranteedPlugin from './unzer/unzer-sepa-direct-debit-guaranteed.plugin';
import UnzerPaymentHirePurchasePlugin from './unzer/unzer-hire-purchase.plugin';
import UnzerPaymentPayPalPlugin from './unzer/unzer-paypal.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('UnzerPaymentBase', UnzerPaymentBasePlugin, '[data-unzer-base]');
PluginManager.register('UnzerPaymentCreditCard', UnzerPaymentCreditCardPlugin, '[data-unzer-credit-card]');
PluginManager.register('UnzerPaymentInvoice', UnzerPaymentInvoicePlugin, '[data-unzer-invoice]');
PluginManager.register('UnzerPaymentInvoiceGuaranteed', UnzerPaymentInvoiceGuaranteedPlugin, '[data-unzer-invoice-guaranteed]');
PluginManager.register('UnzerPaymentInvoiceFactoring', UnzerPaymentInvoiceFactoringPlugin, '[data-unzer-invoice-factoring]');
PluginManager.register('UnzerPaymentEps', UnzerPaymentEpsPlugin, '[data-unzer-eps]');
PluginManager.register('UnzerPaymentIdeal', UnzerPaymentIdealPlugin, '[data-unzer-ideal]');
PluginManager.register('UnzerPaymentSepaDirectDebit', UnzerPaymentSepaDirectDebitPlugin, '[data-unzer-sepa-direct-debit]');
PluginManager.register('UnzerPaymentSepaDirectDebitGuaranteed', UnzerPaymentSepaDirectDebitGuaranteedPlugin, '[data-unzer-sepa-direct-debit-guaranteed]');
PluginManager.register('UnzerPaymentHirePurchase', UnzerPaymentHirePurchasePlugin, '[data-unzer-hire-purchase]');
PluginManager.register('UnzerPaymentPaypal', UnzerPaymentPayPalPlugin, '[data-unzer-paypal]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
