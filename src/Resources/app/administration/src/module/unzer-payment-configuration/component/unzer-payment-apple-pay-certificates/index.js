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
        },
        parentRefs: {
            required: true
        },
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
                        this.merchantIdentificationValidUntil = response.merchantIdentificationValidUntil ? new Date(response.merchantIdentificationValidUntil) : null;
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

        resetFileFieldsMerchantIdentification() {
            this.$refs.merchantIdentificationCertificateInput.onRemoveIconClick();
            this.$refs.merchantIdentificationKeyInput.onRemoveIconClick();
        },
        resetFileFieldsPaymentProcessing() {
            this.$refs.paymentProcessingCertificateInput.onRemoveIconClick();
            this.$refs.paymentProcessingKeyInput.onRemoveIconClick();
        },

        updateCertificates() {
            const me = this;
            this.isUpdateSuccessful = false;
            this.isUpdating = true;

            if (
                !this.paymentProcessingCertificate
                && !this.paymentProcessingKey
                && !this.merchantIdentificationCertificate
                && !this.merchantIdentificationKey
                && !this.$refs.inheritWrapperMerchantIdentificationCertificate.isInherited
                && !this.$refs.inheritWrapperPaymentProcessingCertificate.isInherited
            ) {
                this.isUpdateSuccessful = true;
                me.isUpdating = false;
                return;
            }

            return this.UnzerPaymentApplePayService.updateCertificates(
                this.selectedSalesChannelId,
                {
                    paymentProcessingCertificate: this.paymentProcessingCertificate,
                    paymentProcessingKey: this.paymentProcessingKey,
                    merchantIdentificationCertificate: this.merchantIdentificationCertificate,
                    merchantIdentificationKey: this.merchantIdentificationKey,
                },
                this.$refs.inheritWrapperMerchantIdentificationCertificate.isInherited,
                this.$refs.inheritWrapperPaymentProcessingCertificate.isInherited
            )
                .then((response) => {
                    me.isUpdateSuccessful = true;

                    me.createNotificationSuccess({
                        title: me.$tc('unzer-payment-settings.apple-pay.certificates.update.success.title'),
                        message: me.$tc('unzer-payment-settings.apple-pay.certificates.update.success.message')
                    });

                    me.$emit('certificate-updated', response);
                    me.parentRefs.systemConfig.loadCurrentSalesChannelConfig();
                    me.checkCertificates();
                    me.resetFileFieldsPaymentProcessing();
                    me.resetFileFieldsMerchantIdentification();
                })
                .catch((errorResponse) => {
                    let message = errorResponse?.response?.data?.message;
                    if (!message) {
                        message = 'unzer-payment-settings.apple-pay.certificates.update.error.message';
                    }

                    me.createNotificationError({
                        title: me.$tc('unzer-payment-settings.apple-pay.certificates.update.error.title'),
                        message: me.$t(message, errorResponse?.response?.data?.translationData ?? {})
                    });
                })
                .finally(() => {
                    me.isUpdating = false;
                });
        },

        onInputChangePaymentProcessing(value) {
            if (value) {
                // Other field is handled with inheritance wrapper event
                this.$refs.inheritWrapperPaymentProcessingCertificate.removeInheritance();
            }
        },
        onInputChangeMerchantIdentification(value) {
            if (value) {
                // Other field is handled with inheritance wrapper event
                this.$refs.inheritWrapperMerchantIdentificationCertificate.removeInheritance();
            }
        },

        setPaymentProcessingInheritance() {
            this.$refs.inheritWrapperPaymentProcessingCertificate.isInherited && this.$refs.inheritWrapperPaymentProcessingCertificate.restoreInheritance();
            this.$refs.inheritWrapperPaymentProcessingKey.isInherited && this.$refs.inheritWrapperPaymentProcessingKey.restoreInheritance();
            this.resetFileFieldsPaymentProcessing();
        },
        removePaymentProcessingInheritance() {
            !this.$refs.inheritWrapperPaymentProcessingCertificate.isInherited && this.$refs.inheritWrapperPaymentProcessingCertificate.removeInheritance();
            !this.$refs.inheritWrapperPaymentProcessingKey.isInherited && this.$refs.inheritWrapperPaymentProcessingKey.removeInheritance();
        },
        setMerchantIdentificationInheritance() {
            this.$refs.inheritWrapperMerchantIdentificationCertificate.isInherited && this.$refs.inheritWrapperMerchantIdentificationCertificate.restoreInheritance();
            this.$refs.inheritWrapperMerchantIdentificationKey.isInherited && this.$refs.inheritWrapperMerchantIdentificationKey.restoreInheritance();
            this.resetFileFieldsMerchantIdentification();
        },
        removeMerchantIdentificationInheritance() {
            !this.$refs.inheritWrapperMerchantIdentificationCertificate.isInherited && this.$refs.inheritWrapperMerchantIdentificationCertificate.removeInheritance();
            !this.$refs.inheritWrapperMerchantIdentificationKey.isInherited && this.$refs.inheritWrapperMerchantIdentificationKey.removeInheritance();
        },

        getInheritedValue(name) {
            return this.parentRefs.systemConfig.getInheritedValue({ name: 'UnzerPayment6.settings.' + name, type: 'text' });
        },
    }
});
