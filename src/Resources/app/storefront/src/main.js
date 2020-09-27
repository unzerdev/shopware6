import UnzerPaymentBasePlugin from './unzer/unzer-base.plugin';
import UnzerPaymentCreditCardPlugin from './unzer/unzer-credit-card.plugin';
import UnzerPaymentEpsPlugin from './unzer/unzer-eps.plugin';
import UnzerPaymentHirePurchasePlugin from './unzer/unzer-hire-purchase.plugin';
import UnzerPaymentIdealPlugin from './unzer/unzer-ideal.plugin';
import UnzerPaymentInvoicePlugin from './unzer/unzer-invoice.plugin';
import UnzerPaymentInvoiceFactoringPlugin from './unzer/unzer-invoice-factoring.plugin';
import UnzerPaymentInvoiceGuaranteedPlugin from './unzer/unzer-invoice-guaranteed.plugin';
import UnzerPaymentPayPalPlugin from './unzer/unzer-paypal.plugin';
import UnzerPaymentSepaDirectDebitPlugin from './unzer/unzer-sepa-direct-debit.plugin';
import UnzerPaymentSepaDirectDebitGuaranteedPlugin from './unzer/unzer-sepa-direct-debit-guaranteed.plugin';

window.PluginManager.register('UnzerPaymentBase', UnzerPaymentBasePlugin, '[data-unzer-payment-base]');
window.PluginManager.register('UnzerPaymentCreditCard', UnzerPaymentCreditCardPlugin, '[data-unzer-payment-credit-card]');
window.PluginManager.register('UnzerPaymentEps', UnzerPaymentEpsPlugin, '[data-unzer-payment-eps]');
window.PluginManager.register('UnzerPaymentHirePurchase', UnzerPaymentHirePurchasePlugin, '[data-unzer-payment-hire-purchase]');
window.PluginManager.register('UnzerPaymentIdeal', UnzerPaymentIdealPlugin, '[data-unzer-payment-ideal]');
window.PluginManager.register('UnzerPaymentInvoice', UnzerPaymentInvoicePlugin, '[data-unzer-payment-invoice]');
window.PluginManager.register('UnzerPaymentInvoiceFactoring', UnzerPaymentInvoiceFactoringPlugin, '[data-unzer-payment-invoice-factoring]');
window.PluginManager.register('UnzerPaymentInvoiceGuaranteed', UnzerPaymentInvoiceGuaranteedPlugin, '[data-unzer-payment-invoice-guaranteed]');
window.PluginManager.register('UnzerPaymentPayPal', UnzerPaymentPayPalPlugin, '[data-unzer-payment-paypal]');
window.PluginManager.register('UnzerPaymentSepaDirectDebit', UnzerPaymentSepaDirectDebitPlugin, '[data-unzer-payment-sepa-direct-debit]');
window.PluginManager.register('UnzerPaymentSepaDirectDebitGuaranteed', UnzerPaymentSepaDirectDebitGuaranteedPlugin, '[data-unzer-payment-sepa-direct-debit-guaranteed]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
