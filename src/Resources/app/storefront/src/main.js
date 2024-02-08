import UnzerPaymentBasePlugin from './unzer/unzer-payment.base.plugin';
import UnzerPaymentCreditCardPlugin from './unzer/unzer-payment.credit-card.plugin';
import UnzerPaymentEpsPlugin from './unzer/unzer-payment.eps.plugin';
import UnzerPaymentInstallmentSecuredPlugin from './unzer/unzer-payment.installment-secured.plugin';
import UnzerPaymentIdealPlugin from './unzer/unzer-payment.ideal.plugin';
import UnzerPaymentInvoicePlugin from './unzer/unzer-payment.invoice.plugin';
import UnzerPaymentInvoiceSecuredPlugin from './unzer/unzer-payment.invoice-secured.plugin';
import UnzerPaymentPayPalPlugin from './unzer/unzer-payment.paypal.plugin';
import UnzerPaymentSepaDirectDebitPlugin from './unzer/unzer-payment.sepa-direct-debit.plugin';
import UnzerPaymentSepaDirectDebitSecuredPlugin from './unzer/unzer-payment.sepa-direct-debit-secured.plugin';
import UnzerPaymentPaylaterInvoicePlugin from "./unzer/unzer-payment.paylater-invoice.plugin";
import UnzerPaymentApplePayPlugin from './unzer/unzer-payment.apple-pay.plugin';
import UnzerPaymentPaylaterInstallmentPlugin from './unzer/unzer-payment.paylater-installment.plugin';

window.PluginManager.register('UnzerPaymentBase', UnzerPaymentBasePlugin, '[data-unzer-payment-base]');
window.PluginManager.register('UnzerPaymentCreditCard', UnzerPaymentCreditCardPlugin, '[data-unzer-payment-credit-card]');
window.PluginManager.register('UnzerPaymentEps', UnzerPaymentEpsPlugin, '[data-unzer-payment-eps]');
window.PluginManager.register('UnzerPaymentIdeal', UnzerPaymentIdealPlugin, '[data-unzer-payment-ideal]');
window.PluginManager.register('UnzerPaymentInvoice', UnzerPaymentInvoicePlugin, '[data-unzer-payment-invoice]');
window.PluginManager.register('UnzerPaymentInvoiceSecured', UnzerPaymentInvoiceSecuredPlugin, '[data-unzer-payment-invoice-secured]');
window.PluginManager.register('UnzerPaymentInstallmentSecured', UnzerPaymentInstallmentSecuredPlugin, '[data-unzer-payment-installment-secured]');
window.PluginManager.register('UnzerPaymentPayPal', UnzerPaymentPayPalPlugin, '[data-unzer-payment-paypal]');
window.PluginManager.register('UnzerPaymentSepaDirectDebit', UnzerPaymentSepaDirectDebitPlugin, '[data-unzer-payment-sepa-direct-debit]');
window.PluginManager.register('UnzerPaymentSepaDirectDebitSecured', UnzerPaymentSepaDirectDebitSecuredPlugin, '[data-unzer-payment-sepa-direct-debit-secured]');
window.PluginManager.register('UnzerPaymentApplePay', UnzerPaymentApplePayPlugin, '[data-unzer-payment-apple-pay]');
window.PluginManager.register('UnzerPaymentPaylaterInvoice', UnzerPaymentPaylaterInvoicePlugin, '[data-unzer-payment-paylater-invoice]');
window.PluginManager.register('UnzerPaymentPaylaterInstallment', UnzerPaymentPaylaterInstallmentPlugin, '[data-unzer-payment-paylater-installment]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
