const { Component } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

// extend the existing component `sw-entity-single-select` by
// overwriting the default criteria
Component.extend(
    'unzer-entity-multi-select-delivery-status',
    'sw-entity-multi-id-select',
    {
        inject: ['repositoryFactory'],
        props: {
            repository: {
                type: Object,
                required: true,
                default() {
                    return this.repositoryFactory.create('state_machine_state');
                },
            },
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
                },
            },
            entityCollection() {},
        },
    }
);
