import UnzerPaymentBasePlugin from './unzer/unzer-payment.base.plugin';
import UnzerPaymentCreditCardPlugin from './unzer/unzer-payment.credit-card.plugin';
import UnzerPaymentPayPalPlugin from './unzer/unzer-payment.paypal.plugin';
import UnzerPaymentSepaDirectDebitPlugin from './unzer/unzer-payment.sepa-direct-debit.plugin';
import UnzerPaymentPaylaterInvoicePlugin from './unzer/unzer-payment.paylater-invoice.plugin';
import UnzerPaymentApplePayV2Plugin from './unzer/unzer-payment.apple-pay-v2.plugin';
import UnzerPaymentPaylaterInstallmentPlugin from './unzer/unzer-payment.paylater-installment.plugin';
import UnzerPaymentPaylaterDirectDebitSecuredPlugin from './unzer/unzer-payment.paylater-direct-debit-secured.plugin';
import UnzerPaymentGooglePayPlugin from './unzer/unzer-payment.google-pay.plugin';
import UnzerPaymentExpressButtonsPlugin from './unzer/express/unzer-payment.express-buttons.plugin';

window.PluginManager.register(
    'UnzerPaymentBase',
    UnzerPaymentBasePlugin,
    '[data-unzer-payment-base]'
);
window.PluginManager.register(
    'UnzerPaymentCreditCard',
    UnzerPaymentCreditCardPlugin,
    '[data-unzer-payment-credit-card]'
);
window.PluginManager.register(
    'UnzerPaymentPayPal',
    UnzerPaymentPayPalPlugin,
    '[data-unzer-payment-paypal]'
);
window.PluginManager.register(
    'UnzerPaymentSepaDirectDebit',
    UnzerPaymentSepaDirectDebitPlugin,
    '[data-unzer-payment-sepa-direct-debit]'
);
window.PluginManager.register(
    'UnzerPaymentApplePayV2',
    UnzerPaymentApplePayV2Plugin,
    '[data-unzer-payment-apple-pay-v2]'
);
window.PluginManager.register(
    'UnzerPaymentPaylaterInvoice',
    UnzerPaymentPaylaterInvoicePlugin,
    '[data-unzer-payment-paylater-invoice]'
);
window.PluginManager.register(
    'UnzerPaymentPaylaterInstallment',
    UnzerPaymentPaylaterInstallmentPlugin,
    '[data-unzer-payment-paylater-installment]'
);
window.PluginManager.register(
    'UnzerPaymentPaylaterDirectDebitSecured',
    UnzerPaymentPaylaterDirectDebitSecuredPlugin,
    '[data-unzer-payment-paylater-direct-debit-secured]'
);
window.PluginManager.register(
    'UnzerPaymentGooglePay',
    UnzerPaymentGooglePayPlugin,
    '[data-unzer-payment-google-pay]'
);
window.PluginManager.register(
    'UnzerPaymentExpressButtons',
    UnzerPaymentExpressButtonsPlugin,
    '[data-unzer-payment-express-buttons]'
);

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
