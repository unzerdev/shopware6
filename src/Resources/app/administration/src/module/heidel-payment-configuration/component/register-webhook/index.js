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
            ]
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        salesChannelDomainCriteria() {
            const criteria = new Shopware.Data.Criteria();

            criteria.addAssociation('salesChannel');
            criteria.addFilter(Shopware.Data.Criteria.equals('salesChannel.active', 1));

            return criteria;
        }
    },

    data() {
        return {
            showWebhookModal: false,
            isLoading: false,
            isRegistering: false,
            isRegistrationSuccessful: false,
            salesChannelDomains: {},
            selection: [],
            clearWebhooks: false
        };
    },

    created() {
        this.salesChannelDomainRepository.search(this.salesChannelDomainCriteria, Shopware.Context.api)
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
            var me = this;
            this.isRegistrationSuccessful = false;
            this.isRegistering = true;
            this.isLoading = true;

            this.HeidelPaymentConfigurationService.registerWebhooks({
                selection: me.selection,
                clearWebhooks: me.clearWebhooks
            })
                .then((response) => {
                    me.isRegistrationSuccessful = true;

                    if(undefined !== response['clear']) {
                        me.messageGeneration(response['clear']);
                    }

                    if(undefined !== response['register']) {
                        me.messageGeneration(response['register']);
                    }
                })
                .catch((response) => {
                    if(undefined !== response['clear']) {
                        me.messageGeneration(response['clear']);
                    }

                    if(undefined !== response['register']) {
                        me.messageGeneration(response['register']);
                    }
                    this.createNotificationError({
                        title: this.$tc('heidel-payment-settings.modal.webhook.error.title'),
                        response:  this.$tc('heidel-payment-settings.modal.webhook.error.response'),
                    });
                })
                .finally((response) => {
                    me.isLoading = false;
                });
        },

        onRegistrationFinished() {
            this.isRegistrationSuccessful = false;
        },

        onSelectItem(id, selected) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                window.console.log(selection);
                if (selection.id === id) {
                    selection.selected = selected;
                }
            });
        },

        _populateSelectionProperty() {
            this.salesChannelDomains.forEach((domain) => {
                this.selection.push({
                    id: domain.id,
                    url: domain.url
                });
            });
        },

        messageGeneration(data) {
            for(const domain in data) {
                if (undefined !== data[domain]) {
                    if (undefined !== data[domain]['message']) {
                        this.createNotificationSuccess({
                            title: this.$tc(data[domain]['message']),
                            message: this.$tc('heidel-payment-settings.webhook.messagePrefix') + domain,
                        });
                    } else {
                        this.createNotificationSuccess({
                            title: this.$tc(data[domain]),
                            message: this.$tc('heidel-payment-settings.webhook.messagePrefix') + domain,
                        });
                    }
                }
            }
        }
    }
});
