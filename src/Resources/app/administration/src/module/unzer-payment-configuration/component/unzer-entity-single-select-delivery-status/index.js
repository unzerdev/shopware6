const { Component } = Shopware;
const { Criteria } = Shopware.Data;

// extend the existing component `sw-entity-single-select` by
// overwriting the default criteria
Component.extend('unzer-entity-single-select-delivery-status', 'sw-entity-single-select', {

    props: {
        criteria: {
            type: Object,
            required: false,
            default() {
                const criteria = new Criteria(1, 100);

                criteria.addFilter(
                    Criteria.equals(
                        'stateMachine.technicalName',
                        'order_delivery.state'
                    )
                );

                return criteria;
            }
        }
    }
});
