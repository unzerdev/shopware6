const { Component, Service } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

Component.extend(
    'unzer-entity-multi-select-delivery-status',
    'sw-entity-multi-id-select',
    {
        props: {
            repository: {
                type: Object,
                required: true,
                default() {
                    return Service('repositoryFactory').create(
                        'state_machine_state'
                    );
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
        },
    }
);
