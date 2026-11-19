const { Application, Service, Store } = Shopware;
const { Criteria } = Shopware.Data;

export default function addUnzerPaymentWebhookUpdateListener() {
    let applicationRoot = null;
    Application.viewInitialized.then(checkForWebhookUpdates);

    async function checkForWebhookUpdates() {
        if (!Shopware.Service('acl').can('system_config.viewer')) {
            return;
        }

        try {
            const config = await Service('systemConfigApiService').getValues(
                'UnzerPayment6.settings'
            );
            const privateKey = config['UnzerPayment6.settings.privateKey'];

            if (!privateKey) {
                return;
            }

            const criteria = new Criteria();
            criteria.addAssociation('domains');

            const [salesChannels, webhooks] = await Promise.all([
                Service('repositoryFactory')
                    .create('sales_channel')
                    .search(criteria, Shopware.Context.api),
                Service('UnzerPaymentConfigurationService').getWebhooks(
                    privateKey
                ),
            ]);

            if (
                !salesChannels ||
                !salesChannels.length ||
                !webhooks ||
                !webhooks.length
            ) {
                return;
            }

            for (const salesChannel of salesChannels) {
                const domains = salesChannel.domains || [];

                for (const domain of domains) {
                    const webhook = webhooks.find(
                        (item) =>
                            item.url.indexOf(domain.url) > -1 &&
                            item.event !== 'all'
                    );

                    if (webhook) {
                        createNotification();

                        return;
                    }
                }
            }
        } catch (error) {
            // silent
        }
    }

    function createNotification() {
        Store.get('notification').createNotification({
            title: getApplicationRootReference().$t('global.default.warning'),
            message: getApplicationRootReference().$t(
                'unzer-payment-settings.webhook.needsUpdate'
            ),
            actions: [
                {
                    label: getApplicationRootReference().$t(
                        'unzer-payment-settings.webhook.notification.openSettings'
                    ),
                    route: {
                        name: 'unzer.payment.configuration.settings',
                    },
                },
            ],
            variant: 'warning',
            appearance: 'notification',
            growl: true,
        });
    }

    function getApplicationRootReference() {
        if (!applicationRoot) {
            applicationRoot = Application.getApplicationRoot();
        }

        return applicationRoot;
    }
}

addUnzerPaymentWebhookUpdateListener();
