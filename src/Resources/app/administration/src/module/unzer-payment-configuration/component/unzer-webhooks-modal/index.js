import template from './unzer-webhooks-modal.html.twig';

const { Component, Mixin, Context } = Shopware;

Component.register('unzer-webhooks-modal', {
    template,

    mixins: [
        Mixin.getByName('notification'),
    ],

    inject: [
        'UnzerPaymentConfigurationService'
    ],

    props: {
        keyPair: {
            type: Array,
            required: true
        },
        webhooks: {
            type: Array,
            required: true,
        },
        isLoadingWebhooks: {
            type: Boolean,
        }
    },

    data() {
        return {
            isClearing: false,
            isClearingSuccessful: false,
            webhookSelection: null,
            webhookSelectionLength: 0,
        };
    },

    computed: {
        webhookColumns() {
            return [
                {
                    property: 'event',
                    dataIndex: 'event',
                    label: 'Event'
                },
                {
                    property: 'url',
                    dataIndex: 'url',
                    label: 'URL'
                }
            ];
        },
    },

    methods: {
        clearWebhooks(privateKey) {
            const me = this;
            this.isClearingSuccessful = false;
            this.isClearing = true;
            this.isLoading = true;

            this.UnzerPaymentConfigurationService.clearWebhooks({
                privateKey: privateKey,
                selection: this.webhookSelection
            })
                .then((response) => {
                    me.isClearingSuccessful = true;
                    me.webhookSelection = [];
                    me.webhookSelectionLength = 0;

                    me.$refs.webhookDataGrid.resetSelection();

                    me.$emit('load-webhooks', privateKey);

                    if (undefined !== response) {
                        me.messageGeneration(response);
                    }
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('unzer-payment-settings.webhook.globalError.title'),
                        message: this.$tc('unzer-payment-settings.webhook.globalError.message')
                    });
                })
                .finally(() => {
                    me.isLoading = false;
                    me.isClearing = false;
                });
        },

        onClearingFinished() {
            this.isClearingSuccessful = false;
            this.isClearing = false;
        },

        onSelectWebhook(selectedItems) {
            this.webhookSelectionLength = Object.keys(selectedItems).length;
            this.webhookSelection = selectedItems;
        },

        messageGeneration(data) {
            const domainAmount = data.length;

            Object.keys(data).forEach((url) => {
                if (data[url].success) {
                    this.createNotificationSuccess({
                        title: this.$tc(data[url].message, domainAmount),
                        message: this.$tc('unzer-payment-settings.webhook.messagePrefix', domainAmount) + url
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc(data[url].message, domainAmount),
                        message: this.$tc('unzer-payment-settings.webhook.messagePrefix', domainAmount) + url
                    });
                }
            });
        },
    }
});
