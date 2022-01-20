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
        }
    },

    computed: {
        salesChannelDomainColumns() {
            return [
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
            isModalActive: false,
            isLoading: false,
            isRegistering: false,
            isRegistrationSuccessful: false,
            isClearing: false,
            isClearingSuccessful: false,
            salesChannelDomains: [],
            selection: [],
            webhooks: []
        };
    },

    watch: {
        privateKey() {
            this.loadData();
        }
    },

    methods: {
        loadData() {
            let criteria = new Criteria();

            criteria.addFilter(
                Criteria.prefix('url', 'https://')
            );

            this.UnzerPaymentConfigurationService.getWebhooks(this.privateKey)
                .then((result) => {
                    this.webhooks = result;

                    this.salesChannelDomainRepository.search(criteria, Shopware.Context.api)
                        .then((result) => {
                            this.salesChannelDomains = result;
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


        onSelectItem(selectedItems) {
            this.selection = selectedItems;
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

        isRecordSelectable(item) {
            let isSelectable = true;

            this.webhooks.forEach((webhook) => {
                if (webhook.url.indexOf(item.url) > -1) {
                    isSelectable = false;
                }
            });

            return isSelectable;
        }
    }
});
