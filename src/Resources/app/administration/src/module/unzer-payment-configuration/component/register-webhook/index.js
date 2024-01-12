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
        webhooks: {
            type: Array,
            required: true
        },
        isLoading: {
            type: Boolean,
            required: false
        },
        selectedSalesChannelId: {
            type: String,
            required: false
        },
        privateKey: {
            type: String,
            required: true
        },
        isDisabled: {
            type: Boolean,
            required: false
        }
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        }
    },

    data() {
        return {
            isModalActive: false,
            isRegistering: false,
            isRegistrationSuccessful: false,
            isDataLoading: false,
            selection: {},
            entitySelection: {},
            salesChannels: {}
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadData();
        },

        loadData(page, limit) {
            let me = this;

            me.isDataLoading = true;

            let criteria = new Criteria(page, limit);
            criteria.addAssociation('domains');

            this.salesChannelRepository.search(criteria, Shopware.Context.api)
                .then((result) => {
                    me.salesChannels = result;
                    me.isDataLoading = false;
                });
        },

        onPageChange(args) {
            this.loadData(args.page, args.limit);
        },

        openModal() {
            this.$emit('modal-open');
            this.isModalActive = true;
        },

        closeModal() {
            this.isModalActive = false;
        },

        registerWebhooks() {
            const me = this;
            this.isRegistrationSuccessful = false;
            this.isRegistering = true;

            this.UnzerPaymentConfigurationService.registerWebhooks({
                selection: this.entitySelection
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
                    me.isRegistering = false;
                });
        },

        onRegistrationFinished() {
            this.isRegistrationSuccessful = false;
            this.selection = {};
        },


        onSelectItem(domainId, domain) {
            if (!domain) {
                return;
            }

            domain['privateKey'] = this.privateKey;

            this.entitySelection[domain.salesChannelId] = domain;
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

            const salesChannel = this.getSalesChannelById(salesChannelId);

            if (!this.webhooks.length) {
                return false;
            }

            salesChannel.domains.forEach((domain) => {
                this.webhooks.forEach((webhook) => {
                    if (webhook.url.indexOf(domain.url) > -1) {
                        result = true;
                        return true;
                    }
                });
            });

            return result;
        },

        getSalesChannelById(salesChannelId) {
            let result = null;

            this.salesChannels.forEach((salesChannel) => {
                if (salesChannel.id === salesChannelId) {
                    result = salesChannel;
                    return true;
                }
            });

            return result;
        },

        getSalesChannelDomainCriteria(salesChannelId) {
            let criteria = new Criteria();

            criteria.addFilter(Criteria.prefix('url', 'https://'));
            criteria.addFilter(Criteria.equals('salesChannelId', salesChannelId));

            return criteria;
        }
    }
});
