import template from './register-webhook.html.twig';
import './style.scss';

Shopware.Component.register('heidel-payment-register-webhook', {
    template,

    mixins: [
        Shopware.Mixin.getByName('notification')
    ],

    inject: [
        'repositoryFactory',
        'HeidelPaymentConfigurationService'
    ],

    computed: {
        salesChannelDomainColumns() {
            return [
                {
                    property: 'id',
                    dataIndex: 'id',
                    label: 'ID'
                },
                {
                    property: 'url',
                    dataIndex: 'url',
                    label: 'URL'
                }
            ];
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        }
    },

    data() {
        return {
            showWebhookModal: false,
            isLoading: false,
            isRegistering: false,
            isRegistrationSuccessful: false,
            isClearing: false,
            isClearingSuccessful: false,
            salesChannelDomains: {},
            selection: []
        };
    },

    created() {
        this.salesChannelDomainRepository.search(new Shopware.Data.Criteria(), Shopware.Context.api)
            .then((result) => {
                this.salesChannelDomains = result;
            });
    },

    methods: {
        openModal() {
            this.showWebhookModal = true;
        },

        closeModal() {
            this.showWebhookModal = false;
        },

        registerWebhooks() {
            const me = this;
            this.isRegistrationSuccessful = false;
            this.isRegistering = true;
            this.isLoading = true;

            this.HeidelPaymentConfigurationService.registerWebhooks({
                selection: this.selection
            })
                .then((response) => {
                    me.isRegistrationSuccessful = true;

                    if (undefined !== response.register) {
                        me.messageGeneration(response.register);
                    }
                })
                .catch((response) => {
                    if (undefined !== response.register) {
                        me.messageGeneration(response.register);
                    }

                    this.createNotificationError({
                        title: this.$tc('heidel-payment-settings.webhook.register.error.title', response.length),
                        message: this.$tc('heidel-payment-settings.webhook.register.error.message', response.length)
                    });
                })
                .finally(() => {
                    me.isLoading = false;
                    me.isRegistering = false;
                });
        },

        clearWebhooks() {
            const me = this;
            this.isClearingSuccessful = false;
            this.isClearing = true;
            this.isLoading = true;

            this.HeidelPaymentConfigurationService.clearWebhooks({
                selection: this.selection
            })
                .then((response) => {
                    me.isClearingSuccessful = true;

                    if (undefined !== response.clear) {
                        me.messageGeneration(response.clear);
                    }
                })
                .catch((response) => {
                    if (undefined !== response.clear) {
                        me.messageGeneration(response.clear);
                    }

                    this.createNotificationError({
                        title: this.$tc('heidel-payment-settings.webhook.clear.error.title', response.length),
                        message: this.$tc('heidel-payment-settings.webhook.clear.error.message', response.length)
                    });
                })
                .finally(() => {
                    me.isLoading = false;
                    me.isClearing = false;
                });
        },

        onRegistrationFinished() {
            this.isRegistrationSuccessful = false;
        },

        onClearingFinished() {
            this.isClearingSuccessful = false;
        },

        onSelectItem(selectedItems) {
            this.selection = selectedItems;
        },

        messageGeneration(data) {
            const domainAmount = data.length;
            for (const domain in data) {
                if (undefined !== data[domain]) {
                    if (undefined !== data[domain].message) {
                        this.createNotificationSuccess({
                            title: this.$tc(data[domain].message, domainAmount),
                            message: this.$tc('heidel-payment-settings.webhook.messagePrefix', domainAmount) + domain
                        });
                    } else {
                        this.createNotificationSuccess({
                            title: this.$tc(data[domain], domainAmount),
                            message: this.$tc('heidel-payment-settings.webhook.messagePrefix', domainAmount) + domain
                        });
                    }
                }
            }
        }
    }
});
