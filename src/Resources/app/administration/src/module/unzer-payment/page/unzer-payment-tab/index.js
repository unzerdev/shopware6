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
            this.isLoading = true;
        },

        reloadPaymentDetails() {
            this.resetDataAttributes();
            this.loadData();
        },

        loadData() {
            const orderId = this.$route.params.id;
            const criteria = new Criteria();
            criteria.addAssociation('transactions');

            this.orderRepository.get(orderId, Context.api, criteria).then((order) => {
                this.order = order;

                if (!order.transactions) {
                    return;
                }

                order.transactions.forEach((orderTransaction) => {
                    if (!orderTransaction.customFields) {
                        return;
                    }

                    if (!orderTransaction.customFields.unzer_payment_is_transaction
                        && !orderTransaction.customFields.heidelpay_is_transaction) {
                        return;
                    }

                    this.UnzerPaymentService.fetchPaymentDetails(orderTransaction.id)
                        .then((response) => {
                            this.isLoading = false;

                            this.paymentResources.push(response);
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
        }
    }
});
