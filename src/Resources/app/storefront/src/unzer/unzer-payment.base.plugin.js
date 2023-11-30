import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentBasePlugin extends Plugin {
    static options = {
        publicKey: null,
        shopLocale: null,
        submitButtonId: 'confirmFormSubmit',
        disabledClass: 'disabled',
        resourceIdElementId: 'unzerResourceId',
        confirmFormId: 'confirmOrderForm',
        errorWrapperClass: 'unzer-payment--error-wrapper',
        errorContentSelector: '.unzer-payment--error-wrapper .alert-content',
        errorShouldNotBeEmpty: '%field% should not be empty',
        isOrderEdit: false
    };

    /**
     * @type {Boolean}
     */
    static submitting = false;

    /**
     * @type {Object}
     *
     * @public
     */
    static unzerInstance = null;

    init() {
        this._registerElements();
        this._registerEvents();
    }

    /**
     * @private
     */
    _registerElements() {
        let unzerInstanceOptions = null;

        if(this.options.shopLocale !== null) {
            unzerInstanceOptions = {locale: this.options.shopLocale}
        }

        this.unzerInstance = new window.unzer(this.options.publicKey, unzerInstanceOptions);

        if (this.options.isOrderEdit) {
            this.submitButton = document.getElementById(this.options.confirmFormId).getElementsByTagName('button')[0];
        } else {
            this.submitButton = document.getElementById(this.options.submitButtonId);
        }
        this.confirmForm = document.getElementById(this.options.confirmFormId);
    }

    /**
     * @private
     */
    _registerEvents() {
        this.submitButton.addEventListener('click', this._onSubmitButtonClick.bind(this));
    }

    /**
     * @param {Boolean} active
     *
     * @public
     */
    setSubmitButtonActive(active) {
        if (active) {
            this.submitButton.classList.remove(this.options.disabledClass);
            this.submitButton.disabled = false;
        } else {
            this.submitButton.classList.add(this.options.disabledClass);
            this.submitButton.disabled = true;
        }
    }

    /**
     * @param {Object} resource
     */
    submitResource(resource) {
        const resourceIdElement = document.getElementById(this.options.resourceIdElementId);
        resourceIdElement.value = resource.id;

        this.setSubmitButtonActive(true);
        this.submitButton.click();
        this.setSubmitButtonActive(false);
    }

    /**
     * @param {String} typeId
     */
    submitTypeId(typeId) {
        const resourceIdElement = document.getElementById(this.options.resourceIdElementId);
        resourceIdElement.value = typeId;

        this.setSubmitButtonActive(true);
        this.submitButton.click();
        this.setSubmitButtonActive(false);
    }

    /**
     * @param {Object} error
     * @param {Boolean} append
     */
    showError(error, append = false) {
        const errorWrapper = document.getElementsByClassName(this.options.errorWrapperClass).item(0);
        const errorContent = document.querySelectorAll(this.options.errorContentSelector)[0];

        if (!append || errorContent.innerText === '') {
            errorContent.innerText = error.message;
        } else {
            errorContent.innerText = `${errorContent.innerText}\n${error.message}`;
        }

        errorWrapper.hidden = false;
        errorWrapper.scrollIntoView({ block: 'end', behavior: 'smooth' });

        this.setSubmitButtonActive(true);
    }

    /**
     * @param {Object} error
     * @param {HTMLElement} el
     */
    renderErrorToElement(error, el) {
        const errorWrapper = document.getElementsByClassName(this.options.errorWrapperClass).item(0);
        const errorContent = document.querySelectorAll(this.options.errorContentSelector)[0];

        errorWrapper.hidden = false;
        errorContent.innerText = error.message;

        el.appendChild(errorWrapper);
    }

    /**
     *
     * @param {Object} event
     *
     * @private
     */
    _onSubmitButtonClick(event) {
        if (this.submitting === true) {
            return;
        }

        this.submitting = true;

        event.preventDefault();

        if (!this._validateForm()) {
            this.submitting = false;

            this.setSubmitButtonActive(true);

            return;
        }

        this.setSubmitButtonActive(false);

        this.$emitter.publish('unzerBase_createResource');
    }

    /**
     * @return {Boolean}
     *
     * @private
     */
    _validateForm() {
        let formValid = true;
        const form = document.forms[this.options.confirmFormId].elements;

        this._clearErrorMessage();

        for (let i = 0; i < form.length; i++) {
            const element = form[i];

            if (!element.checkValidity()) {
                if (element.dataset.customError) {
                    this.showError({
                        message: element.dataset.customError
                    });
                }

                element.classList.add('is-invalid');

                return false;
            }

            if (element.required && element.value === '') {
                element.classList.add('is-invalid');

                if (element.labels.length === 0 && formValid) {
                    element.scrollIntoView({ block: 'end', behavior: 'smooth' });
                } else if (element.labels.length > 0) {
                    this.showError({
                        message: this.options.errorShouldNotBeEmpty.replace(/%field%/, element.labels[0].innerText)
                    }, true);
                }

                formValid = false;
            } else {
                element.classList.remove('is-invalid');
            }
        }

        return formValid;
    }

    _clearErrorMessage() {
        const errorWrapper = document.getElementsByClassName(this.options.errorWrapperClass).item(0);
        const errorContent = document.querySelectorAll(this.options.errorContentSelector)[0];

        errorWrapper.hidden = true;
        errorContent.innerText = '';
    }

    /**
     *
     * @param {Object} customerInfo
     * @return {Object}
     *
     * @public
     */
    getB2bCustomerObject(customerInfo) {
        const combinedName = `${customerInfo.firstName} ${customerInfo.lastName}`;
        const birthDate = !customerInfo.birthday ? null : new Date(customerInfo.birthday);
        const customerObject =  {
            firstname: customerInfo.firstName,
            lastname: customerInfo.lastName,
            email: customerInfo.email,
            company: customerInfo.activeBillingAddress.company,
            salutation: customerInfo.salutation.salutationKey,
            billingAddress: {
                name: combinedName,
                street: customerInfo.activeBillingAddress.street,
                zip: customerInfo.activeBillingAddress.zipcode,
                city: customerInfo.activeBillingAddress.city,
                country: customerInfo.activeBillingAddress.country.iso
            },
            shippingAddress: {
                name: combinedName,
                street: customerInfo.activeShippingAddress.street,
                zip: customerInfo.activeShippingAddress.zipcode,
                city: customerInfo.activeShippingAddress.city,
                country: customerInfo.activeShippingAddress.country.iso
            }
        };

        if(birthDate) {
            // @see https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Date/getMonth
            customerObject.birthDate = birthDate.getFullYear() + '-' + (birthDate.getMonth() + 1).toString().padStart(2, '0') + '-' + (birthDate.getDay()).toString().padStart(2, '0')
        }

        return customerObject;
    }
}
