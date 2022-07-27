import template from './unzer-apple-pay-certificates.html.twig';

Shopware.Component.register('unzer-payment-apple-pay-certificates', {
    template,

    mixins: [
        Shopware.Mixin.getByName('notification')
    ],

    inject: [
        'repositoryFactory',
        'UnzerPaymentApplePayService'
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: false
        },
        selectedSalesChannelId: {
            type: String,
            required: false
        }
    },

    data() {
        return {
            isUpdating: false,
            isUpdateSuccessful: false,
            isDataLoading: false,
            paymentProcessingCertificate: false,
            paymentProcessingKey: false,
            merchantIdentificationCertificate: false,
            merchantIdentificationKey: false,
            merchantIdentificationValid: false,
            merchantIdentificationValidUntil: null,
            paymentProcessingValid: false,
        };
    },

    computed: {
        isNotDefaultSalesChannel() {
            return this.selectedSalesChannelId !== null;
        },

        now() {
            return Date.now();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadData();
        },

        loadData() {
            let me = this;

            me.isDataLoading = true;

            this.UnzerPaymentApplePayService.checkCertificates(this.selectedSalesChannelId)
                .then((response) => {
                    if (typeof response !== "undefined") {
                        this.merchantIdentificationValid = response.merchantIdentificationValid;
                        this.merchantIdentificationValidUntil = response.merchantIdentificationValidUntil;
                        this.paymentProcessingValid = response.paymentProcessingValid;
                    }
                })
                .finally(() => {
                    me.isDataLoading = false;
                });
        },

        onSave() {
            return this.updateCertificates();
        },

        updateCertificates() {
            const me = this;
            this.isUpdateSuccessful = false;
            this.isUpdating = true;

            return this.UnzerPaymentApplePayService.updateCertificates(this.selectedSalesChannelId, {
                paymentProcessingCertificate: this.paymentProcessingCertificate,
                paymentProcessingKey: this.paymentProcessingKey,
                merchantIdentificationCertificate: this.merchantIdentificationCertificate,
                merchantIdentificationKey: this.merchantIdentificationKey,
            })
                .then((response) => {
                    me.isUpdateSuccessful = true;

                    this.createNotificationSuccess({
                        title: this.$tc('unzer-payment-settings.apple-pay.certificates.update.success.title'),
                        message: this.$tc('unzer-payment-settings.apple-pay.certificates.update.success.message')
                    });

                    this.$emit('certificate-updated', response);
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('unzer-payment-settings.apple-pay.certificates.update.error.title'),
                        message: this.$tc('unzer-payment-settings.apple-pay.certificates.update.error.message')
                    });
                })
                .finally(() => {
                    me.isUpdating = false;
                });
        },

        getInheritedValue(element) {
            const value = this.actualConfigData.null[element.name];

            if (value) {
                return value;
            }
        },
    }
});
