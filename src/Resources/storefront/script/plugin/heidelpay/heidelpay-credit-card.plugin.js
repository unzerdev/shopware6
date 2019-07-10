import Plugin from 'src/script/plugin-system/plugin.class';

export default class HeidelpayCreditCardPlugin extends Plugin {
    static options = {
        numberFieldId: 'heidelpay-credit-card-number',
        expiryFieldId: 'heidelpay-credit-card-expiry',
        cvcFieldId: 'heidelpay-credit-card-cvc',

        invalidClass: 'is-invalid',
    };

    /**
     * @type { Object }
     *
     * @public
     */
    creditCard;

    /**
     * @type { HeidelpayBasePlugin }
     *
     * @private
     */
    _heidelpayPlugin = null;

    init() {
        this._heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];

        this.createForm();
    }

    createForm() {
        this.creditCard = this._heidelpayPlugin.heidelpayInstance.Card();

        this.creditCard.create('number', {
            containerId: this.options.numberFieldId,
            onlyIframe: true
        });

        this.creditCard.create('expiry', {
            containerId: this.options.expiryFieldId,
            onlyIframe: true
        });

        this.creditCard.create('cvc', {
            containerId: this.options.cvcFieldId,
            onlyIframe: true
        });

        this.creditCard.addEventListener('change', this._onChangeForm);
    }

    _onChangeForm(event) {
        //TODO: Handle change event
    }
}
