import template from './unzer-payment-tab.html.twig';

const { Component, Context, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('unzer-payment-tab', {
    template,

    inject: ['UnzerPaymentService', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            paymentResources: [],
            loadedResources: 0,
            isLoading: true
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        orderRepository() {
            return this.repositoryFactory.create('order');
        }
    },

    watch: {
        '$route'() {
            this.resetDataAttributes();
            this.createdComponent();
        }
    },

    methods: {
        createdComponent() {
            this.loadData();
        },

        resetDataAttributes() {
            this.paymentResources = [];
            this.loadedResources = 0;
            this.isLoading = true;
        },

        reloadPaymentDetails() {
            this.resetDataAttributes();
            this.loadData();
        },

        loadData() {
            const orderId = this.$route.params.id;
            const criteria = new Criteria();
            criteria
                .getAssociation('transactions')
                .addSorting(Criteria.sort('createdAt', 'DESC'));

            this.orderRepository.get(orderId, Context.api, criteria).then((order) => {
                this.order = order;

                if (!order.transactions) {
                    return;
                }

                order.transactions.forEach((orderTransaction, index) => {
                    if (!orderTransaction.customFields) {
                        this.loadedResources++;

                        return;
                    }

                    if (!orderTransaction.customFields.unzer_payment_is_transaction
                        && !orderTransaction.customFields.heidelpay_is_transaction) {
                        this.loadedResources++;

                        return;
                    }

                    this.UnzerPaymentService.fetchPaymentDetails(orderTransaction.id)
                        .then((response) => {
                            this.paymentResources[index] = response;
                            this.loadedResources++;

                            this.isLoading = this.order.transactions.length !== this.loadedResources;
                        })
                        .catch(() => {
                            this.createNotificationError({
                                title: this.$tc('unzer-payment.paymentDetails.notifications.genericErrorMessage'),
                                message: this.$tc('unzer-payment.paymentDetails.notifications.couldNotRetrieveMessage')
                            });

                            this.isLoading = false;
                        });
                });
            });
        },

        reloadOrderDetails() {
            //we cannot know, when the webhook is called, but 5 seconds should be enough to wait for most cases
            setTimeout(() => {
                this.findOrderDetailComponentAndReInit();
            }, 5000);
        },

        async findOrderDetailComponentAndReInit(base = this) {
            const componentName = 'sw-order-detail';
            const parent = base.$parent;

            if (parent === undefined) {
                return null;
            }

            if (parent.$options.name !== componentName) {
                return this.findOrderDetailComponentAndReInit(parent);
            }

            if (parent.isOrderEditing) {
                return null;
            }

            // we reinitialize the orderDetail component, because there is no other way to update  it is not updating the order state
            parent.createdComponent();
        },
    }
});
