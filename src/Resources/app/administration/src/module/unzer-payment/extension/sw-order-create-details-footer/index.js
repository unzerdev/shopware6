import template from './sw-order-create-details-footer.html.twig';

const { Criteria } = Shopware.Data;
const unzerPaymentHandler = 'UnzerPayment6\\Components';

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
                    Criteria.contains('handlerIdentifier', unzerPaymentHandler)
                ])
            );

            return criteria;
        }
    }
});
