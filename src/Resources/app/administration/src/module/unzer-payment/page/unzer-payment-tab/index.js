import template from './unzer-payment-tab.html.twig';

const { Component, StateDeprecated } = Shopware;

Component.register('unzer-payment-tab', {
    template,

    inject: ['UnzerPaymentService'],

    data() {
        return {
            paymentResources: [],
            isLoading: true
        };
    },

    created() {
        this.createdComponent();
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

        orderStore() {
            return StateDeprecated.getStore('order');
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

            this.orderStore().getByIdAsync(orderId).then((order) => {
                this.order = order;

                this.order.getAssociation('transactions').getList({}).then((orderTransactions) => {
                    orderTransactions.items.forEach((orderTransaction) => {
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
                                this.isLoading = false;
                            });
                    });
                });
            });
        }
    }
});
