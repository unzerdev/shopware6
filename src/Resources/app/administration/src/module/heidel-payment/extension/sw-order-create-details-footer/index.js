const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;
const heidelPaymentHandler = 'HeidelPayment6\\Components';

import template from './sw-order-create-details-footer.html.twig';

Component.override('sw-order-create-details-footer', {
    template,

    computed: {
        paymentMethodCriteria() {
            /** @var {Criteria} paymentCriteria */
            const paymentCriteria = this.salesChannelCriteria;

            paymentCriteria.addFilter(
                Criteria.not('AND', [
                    Criteria.contains('handlerIdentifier', heidelPaymentHandler)
                ])
            );

            window.console.log(paymentCriteria);

            return paymentCriteria;
        },
    },
});
