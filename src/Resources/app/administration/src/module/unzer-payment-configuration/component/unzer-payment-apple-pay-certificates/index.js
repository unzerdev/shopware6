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
            this.checkCertificates();
        },

        checkCertificates() {
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

                    me.createNotificationSuccess({
                        title: me.$tc('unzer-payment-settings.apple-pay.certificates.update.success.title'),
                        message: me.$tc('unzer-payment-settings.apple-pay.certificates.update.success.message')
                    });

                    me.$emit('certificate-updated', response);
                    me.checkCertificates();
                    me.$refs.paymentProcessingCertificateInput.onRemoveIconClick();
                    me.$refs.paymentProcessingKeyInput.onRemoveIconClick();
                    me.$refs.merchantIdentificationCertificateInput.onRemoveIconClick();
                    me.$refs.merchantIdentificationKeyInput.onRemoveIconClick();
                })
                .catch(() => {
                    me.createNotificationError({
                        title: me.$tc('unzer-payment-settings.apple-pay.certificates.update.error.title'),
                        message: me.$tc('unzer-payment-settings.apple-pay.certificates.update.error.message')
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
