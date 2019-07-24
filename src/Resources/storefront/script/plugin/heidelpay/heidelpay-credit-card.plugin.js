import Plugin from 'src/script/plugin-system/plugin.class';
import DomAccess from 'src/script/helper/dom-access.helper';

export default class HeidelpayCreditCardPlugin extends Plugin {
    static options = {
        numberFieldId: 'heidelpay-credit-card-number',
        expiryFieldId: 'heidelpay-credit-card-expiry',
        cvcFieldId: 'heidelpay-credit-card-cvc',
        invalidClass: 'is-invalid',
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static creditCard;

    /**
     * @type {Boolean}
     */
    static submitting = false;

    /**
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static _heidelpayPlugin = null;

    init() {
        this._heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];

        this._heidelpayPlugin.setSubmitButtonActive(false);
        this._createForm();
        this._registerEvents();
    }

    _createForm() {
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

        this.creditCard.addEventListener('change', this._onChangeForm.bind(this));
    }

    _registerEvents() {
        this._heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onChangeForm(event) {
        if (!event.type || this.submitting) {
            return;
        }

        let inputElement = this._getInputElementByEvent(event);
        let errorElement = this._getErrorElementByEvent(event);

        if (event.success === false) {
            inputElement.classList.add(this.options.invalidClass);
            errorElement.hidden = false;
        } else if(event.success === true) {
            inputElement.classList.remove(this.options.invalidClass);
            errorElement.hidden = true;
        }

        if (event.error) {
            let errorMessageElement = errorElement.getElementsByClassName('heidelpay-error-message')[0];
            errorMessageElement.innerText = event.error;
        }

        if (event.type === 'cvc') {
            this.cvcValid = event.success;
        } else if (event.type === 'number') {
            this.numberValid = event.success;
        } else if (event.type === 'expiry') {
            this.expiryValid = event.success;
        }

        this._heidelpayPlugin.setSubmitButtonActive(
            this.cvcValid === true &&
            this.numberValid === true &&
            this.expiryValid === true
        );
    }

    _onCreateResource() {
        this.submitting = true;
        this._heidelpayPlugin.setSubmitButtonActive(false);

        this.creditCard.createResource()
            .then((resource) => this._submitPayment(resource))
            .catch((error) => this._handleError(error));
    }

    /**
     * @param {Object} event
     * @returns {HTMLElement}
     *
     * @private
     */
    _getInputElementByEvent(event) {
        let selector = `#heidelpay-credit-card-${event.type}`;

        return DomAccess.querySelector(this.el, selector);
    }

    /**
     * @param {Object} event
     * @returns {HTMLElement}
     *
     * @private
     */
    _getErrorElementByEvent(event) {
        let selector = `#heidelpay-credit-card-${event.type}-error`;

        return DomAccess.querySelector(this.el, selector);
    }

    /**
     * @param {Object} resource
     * @private
     */
    _submitPayment(resource) {
        this._heidelpayPlugin.submit(resource);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this._heidelpayPlugin.showError(error);
    }
}
