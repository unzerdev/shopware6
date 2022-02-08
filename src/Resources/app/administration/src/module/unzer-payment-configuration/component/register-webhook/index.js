import template from './register-webhook.html.twig';
import './style.scss';

const Criteria = Shopware.Data.Criteria;

Shopware.Component.register('unzer-payment-register-webhook', {
    template,

    mixins: [
        Shopware.Mixin.getByName('notification')
    ],

    inject: [
        'repositoryFactory',
        'UnzerPaymentConfigurationService'
    ],


    props: {
        privateKey: {
            type: String,
            required: true,
        },
        webhooks: {
            type: Array,
            required: true
        }
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        salesChannelDomainColumns() {
            return [
                {
                    property: 'url',
                    dataIndex: 'url',
                    label: 'URL'
                }
            ];
        }
    },

    data() {
        return {
            isModalActive: false,
            isLoading: false,
            isRegistering: false,
            isRegistrationSuccessful: false,
            selection: {},
            salesChannels: [],
            salesChannelDomains: {}
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            let me = this;

            me.salesChannelRepository.search(new Criteria(), Shopware.Context.Api)
                .then((result) => {
                    me.salesChannels = result;

                    me.salesChannels.forEach((salesChannel) => {
                        let criteria = new Criteria();
                        criteria.addFilter(Criteria.prefix('url', 'https://'));
                        criteria.addFilter(Criteria.equals('salesChannelId', salesChannel.id));

                        me.salesChannelDomainRepository.search(criteria, Shopware.Context.Api)
                            .then((result) => {
                                me.salesChannelDomains[salesChannel.id] = result;
                            });
                    });

                });
        },

        openModal() {
            this.isModalActive = true;
        },

        closeModal() {
            this.isModalActive = false;
        },

        registerWebhooks() {
            const me = this;
            this.isRegistrationSuccessful = false;
            this.isRegistering = true;
            this.isLoading = true;

            this.UnzerPaymentConfigurationService.registerWebhooks({
                selection: this.selection
            })
                .then((response) => {
                    me.isRegistrationSuccessful = true;

                    if (undefined !== response) {
                        me.messageGeneration(response);
                    }

                    this.$emit('webhook-registered', response);
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('unzer-payment-settings.webhook.globalError.title'),
                        message: this.$tc('unzer-payment-settings.webhook.globalError.message')
                    });
                })
                .finally(() => {
                    me.isLoading = false;
                    me.isRegistering = false;
                });
        },

        onRegistrationFinished() {
            this.isRegistrationSuccessful = false;
        },


        onSelectItem(selectedItems, selectedItem, selected) {
            if (selected) {
                this.$set(this.selection, selectedItem.id, selectedItem);
            } else if (!selected && this.selection[selectedItem.id]) {
                this.$delete(this.selection, selectedItem.id);
            }
        },

        messageGeneration(data) {
            const domainAmount = data.length;

            Object.keys(data).forEach((domain) => {
                if (data[domain].success) {
                    this.createNotificationSuccess({
                        title: this.$tc(data[domain].message, domainAmount),
                        message: this.$tc('unzer-payment-settings.webhook.messagePrefix', domainAmount) + domain
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc(data[domain].message, domainAmount),
                        message: this.$tc('unzer-payment-settings.webhook.messagePrefix', domainAmount) + domain
                    });
                }
            });
        },

        isWebhookRegisteredForSalesChannel(salesChannelId) {
            let result = false;

            this.salesChannelDomains[salesChannelId].forEach((domain) => {
                this.webhooks.forEach((webhook) => {
                    if (webhook.url.indexOf(domain.url) > -1) {
                        result = true;
                    }
                });
            });

            return result;
        }
    }
});
