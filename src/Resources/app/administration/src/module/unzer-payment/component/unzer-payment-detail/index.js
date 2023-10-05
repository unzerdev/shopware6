import template from './unzer-payment-detail.html.twig';

const {Component, Mixin, Module} = Shopware;

Component.register('unzer-payment-detail', {
    template,

    inject: ['UnzerPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSuccessful: false,
            paylaterInvoicePaymentMethodId: '09588ffee8064f168e909ff31889dd7f' // see \UnzerPayment6\Installer\PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE
        };
    },

    props: {
        paymentResource: {
            type: Object,
            required: true
        }
    },

    computed: {
        unzerMaxDigits() {
            const unzerPaymentModule = Module.getModuleRegistry().get('unzer-payment');

            if(!unzerPaymentModule || !unzerPaymentModule.manifest) {
                return 4;
            }

            return unzerPaymentModule.manifest.maxDigits;
        },

        remainingAmount() {
            if(!this.paymentResource || !this.paymentResource.amount) {
                return 0;
            }

            return this.formatAmount(this.paymentResource.amount.remaining, this.paymentResource.amount.decimalPrecision)
        },

        cancelledAmount() {
            if(!this.paymentResource || !this.paymentResource.amount) {
                return 0;
            }

            return this.formatAmount(this.paymentResource.amount.cancelled, this.paymentResource.amount.decimalPrecision)
        },

        chargedAmount() {
            if(!this.paymentResource || !this.paymentResource.amount) {
                return 0;
            }

            return this.formatAmount(this.paymentResource.amount.charged, this.paymentResource.amount.decimalPrecision)
        }
    },

    methods: {
        async findOrderDetailAndReload(base = this) {
            //TODO: sw-order-state-select-v2 is not updating the order state, what is it doing?!

            const componentName = 'sw-order-detail';
            const parent = base.$parent;

            if (parent === undefined) {
                return null;
            }

            if (parent.$options.name !== 'sw-order-detail') {
                this.findOrderDetailAndReload(parent);

                return;
            }

            if (parent.isOrderEditing) {
                return;
            }

            const hasOrderDeepEditState = parent.hasOrderDeepEdit;
            await parent.reloadEntityData();

            //we need to reset it here, because the reloadEntityData() method will set it to true when not within documents
            parent.hasOrderDeepEdit = hasOrderDeepEditState;
        },

        reloadOrderDetail() {
            this.findOrderDetailAndReload();
        },


        ship() {
            this.isLoading = true;

            this.UnzerPaymentService.ship(
                this.paymentResource.orderId
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.shipSuccessTitle'),
                    message: this.$tc('unzer-payment.paymentDetails.notifications.shipSuccessMessage')
                });

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.errors[0];

                if (message === 'generic-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.genericErrorMessage');
                } else if (message === 'invoice-missing-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.invoiceNotFoundMessage');
                } else if(message === 'documentdate-missing-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.documentDateMissingError');
                } else if(message === 'payment-missing-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.paymentMissingError');
                }

                this.createNotificationError({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.shipErrorTitle'),
                    message: message
                });

                this.isLoading = false;
            });
        },

        formatAmount(cents, decimalPrecision) {
            return cents / (10 ** Math.min(this.unzerMaxDigits, decimalPrecision));
        }
    }
});
