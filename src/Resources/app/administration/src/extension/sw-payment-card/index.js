import template from './sw-payment-card.html.twig';
import deDE from '../../snippets/de-DE.json';
import enGB from '../../snippets/en-GB.json';

Shopware.Component.override('sw-payment-card', {
    template,
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },
});
