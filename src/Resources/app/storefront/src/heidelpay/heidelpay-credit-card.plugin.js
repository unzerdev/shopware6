import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class HeidelpayCreditCardPlugin extends Plugin {
    static options = {
        numberFieldId: 'heidelpay-credit-card-number',
        numberFieldInputId: 'heidelpay-credit-card-number-input',
        expiryFieldId: 'heidelpay-credit-card-expiry',
        cvcFieldId: 'heidelpay-credit-card-cvc',
        iconFieldId: 'heidelpay-credit-card-icon',
        invalidClass: 'is-invalid',
        elementWrapperSelector: '.heidelpay-credit-card-wrapper-elements',
        radioButtonSelector: '*[name="savedCreditCard"]',
        radioButtonNewId: 'card-new',
        selectedRadioButtonSelector: '*[name="savedCreditCard"]:checked',
        hasSavedCards: false,
        placeholderBrandImageUrl: 'https://static.heidelpay.com/assets/images/common/group-5.svg',
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

        this._createForm();
        this._registerEvents();

        if (this.options.hasSavedCards) {
            const heidelpayElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);

            heidelpayElementWrapper.hidden = true;
        } else {
            this._heidelpayPlugin.setSubmitButtonActive(false);
        }
    }

    /**
     * @private
     */
    _createForm() {
        this.creditCard = this._heidelpayPlugin.heidelpayInstance.Card();

        this.creditCard.create('number', {
            containerId: this.options.numberFieldInputId,
            onlyIframe: true,
        });

        this.creditCard.create('expiry', {
            containerId: this.options.expiryFieldId,
            onlyIframe: true,
        });

        this.creditCard.create('cvc', {
            containerId: this.options.cvcFieldId,
            onlyIframe: true,
        });

        this.creditCard.addEventListener('change', this._onChangeForm.bind(this));
    }

    /**
     * @private
     */
    _registerEvents() {
        if (this.options.hasSavedCards) {
            const radioButtons = DomAccess.querySelectorAll(this.el, this.options.radioButtonSelector);

            for (let $i = 0; $i < radioButtons.length; $i++) {
                radioButtons[$i].addEventListener('change',  (event) => this._onRadioButtonChange(event));
            }
        }

        this._heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this,
        });
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onRadioButtonChange(event) {
        const targetElement = event.target,
            heidelpayElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);

        heidelpayElementWrapper.hidden = targetElement.id !== this.options.radioButtonNewId;

        if (targetElement.id === this.options.radioButtonNewId) {
            this._heidelpayPlugin.setSubmitButtonActive(
                this.cvcValid === true &&
                this.numberValid === true &&
                this.expiryValid === true
            );
        } else {
            this._heidelpayPlugin.setSubmitButtonActive(true);
        }
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onChangeForm(event) {
        if (event.cardType) {
            let imageUrl = this.options.placeholderBrandImageUrl;

            if (event.cardType.type !== 'unknown') {
                imageUrl = this._getBrandImageUrl(event.cardType.type);
            }

            document.getElementById(this.options.iconFieldId).src = imageUrl;

            return;
        }

        if (!event.type || this.submitting) {
            return;
        }

        const inputElement = this._getInputElementByEvent(event);
        const errorElement = this._getErrorElementByEvent(event);

        if (event.success === false) {
            inputElement.classList.add(this.options.invalidClass);
            errorElement.hidden = false;
        } else if(event.success === true) {
            inputElement.classList.remove(this.options.invalidClass);
            errorElement.hidden = true;
        }

        if (event.error) {
            const errorMessageElement = errorElement.getElementsByClassName('heidelpay-error-message')[0];
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

    /**
     * @private
     */
    _onCreateResource() {
        let checkedRadioButton = null;

        if (this.options.hasSavedCards) {
            checkedRadioButton = DomAccess.querySelector(this.el, this.options.selectedRadioButtonSelector);
        }

        this.submitting = true;
        this._heidelpayPlugin.setSubmitButtonActive(false);

        if (checkedRadioButton === null || checkedRadioButton.id === this.options.radioButtonNewId) {
            this.creditCard.createResource()
                .then((resource) => this._submitPayment(resource))
                .catch((error) => this._handleError(error));
        } else {
            this._heidelpayPlugin.submitTypeId(checkedRadioButton.value);
        }
    }

    /**
     * @param {Object} event
     * @returns {HTMLElement}
     *
     * @private
     */
    _getInputElementByEvent(event) {
        const selector = `#heidelpay-credit-card-${event.type}`;

        return DomAccess.querySelector(this.el, selector);
    }

    /**
     * @param {Object} event
     * @returns {HTMLElement}
     *
     * @private
     */
    _getErrorElementByEvent(event) {
        const selector = `#heidelpay-credit-card-${event.type}-error`;

        return DomAccess.querySelector(this.el, selector);
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this._heidelpayPlugin.submitResource(resource);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this._heidelpayPlugin.showError(error);
    }

    /**
     * @param {String} brand
     *
     * @private
     */
    _getBrandImageUrl(brand) {
        return `https://static.heidelpay.com/assets/images/brands/${brand}.svg`;
    }
}
