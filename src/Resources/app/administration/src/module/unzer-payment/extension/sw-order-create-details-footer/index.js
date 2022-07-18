import template from './sw-order-create-details-footer.html.twig';

const { Criteria } = Shopware.Data;
const unzerPaymentIds = [
    'bc4c2cbfb5fda0bf549e4807440d0a54', //PAYMENT_ID_ALIPAY
    '4673044aff79424a938d42e9847693c3', //PAYMENT_ID_CREDIT_CARD
    '713c7a332b432dcd4092701eda522a7e', //PAYMENT_ID_DIRECT_DEBIT
    '5123af5ce94a4a286641973e8de7eb60', //PAYMENT_ID_DIRECT_DEBIT_SECURED
    '17830aa7e6a00b99eab27f0e45ac5e0d', //PAYMENT_ID_EPS
    '4ebb99451f36ba01f13d5871a30bce2c', //PAYMENT_ID_FLEXIPAY
    'd4b90a17af62c1bb2f6c3b1fed339425', //PAYMENT_ID_GIROPAY
    '4b9f8d08b46a83839fd0eb14fe00efe6', //PAYMENT_ID_INSTALLMENT_SECURED
    '08fb8d9a72ab4ca62b811e74f2eca79f', //PAYMENT_ID_INVOICE
    '6cc3b56ce9b0f80bd44039c047282a41', //PAYMENT_ID_INVOICE_SECURED
    '614ad722a03ee96baa2446793143215b', //PAYMENT_ID_IDEAL
    '409fe641d6d62a4416edd6307d758791', //PAYMENT_ID_PAYPAL
    '085b64d0028a8bd447294e03c4eb411a', //PAYMENT_ID_PRE_PAYMENT
    'cd6f59d572e6c90dff77a48ce16b44db', //PAYMENT_ID_PRZELEWY24
    '95aa098aac8f11e9a2a32a2ae2dbcce4', //PAYMENT_ID_SOFORT
    'fd96d03535a46d197f5adac17c9f8bac', //PAYMENT_ID_WE_CHAT
    '09588ffee8064f168e909ff31889dd7f', //PAYMENT_ID_UNZER_INVOICE
]

Shopware.Component.override('sw-order-create-details-footer', {
    template,

    computed: {
        paymentMethodCriteria() {
            /** @var {Criteria} paymentCriteria */
            const criteria = new Criteria();

            if (this.salesChannelId) {
                criteria.addFilter(Criteria.equals('salesChannels.id', this.salesChannelId));
            }

            criteria.addFilter(
                Criteria.not('AND', [
                    Criteria.equalsAny('id', unzerPaymentIds)
                ])
            );

            return criteria;
        }
    }
});
