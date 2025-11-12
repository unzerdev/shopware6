const Plugin = window.PluginBaseClass;

export default class UnzerPaymentBasePlugin extends Plugin {
    static options = {
        publicKey: null,
        shopLocale: null,
        unzerCustomer: null,
        submitButtonId: 'confirmFormSubmit',
        disabledClass: 'disabled',
        resourceIdElementId: 'unzerResourceId',
        threatMetrixIdElementId: 'unzerThreatMetrixId',
        confirmFormId: 'confirmOrderForm',
        errorWrapperClass: 'unzer-payment--error-wrapper',
        errorContentSelector: '.unzer-payment--error-wrapper .alert-content',
        errorShouldNotBeEmpty: '%field% should not be empty',
        isOrderEdit: false,
        savedDeviceRadioButtonSelector: '*[name="savedPaymentDevice"]',
        savedDeviceRadioButtonNewAccountId: 'device-new',
        savedDeviceSelectedRadioButtonSelector:
            '*[name="savedPaymentDevice"]:checked',
    };

    /**
     * @type {Boolean}
     */
    static submitting = false;

    init() {
        this._registerElements();
        this._registerEvents();
    }

    /**
     * @private
     */
    _registerElements() {
        this.submitButton = this.getSubmitButton();
    }

    getSubmitButton() {
        let submitButton = document.getElementById(this.options.submitButtonId);
        if (!submitButton) {
            submitButton = document
                .getElementById(this.options.confirmFormId)
                .getElementsByTagName('button')[0];
        }
        return submitButton || null;
    }

    /**
     * @private
     */
    _registerEvents() {
        this.submitButton.addEventListener(
            'click',
            this._onSubmitButtonClick.bind(this)
        );
        if (this.options.unzerCustomer) {
            Promise.all([customElements.whenDefined('unzer-payment')]).then(
                () => {
                    const unzerPaymentElement = document.getElementById(
                        'unzer-payment-component'
                    );
                    if (unzerPaymentElement) {
                        console.log(
                            'set customer data',
                            this.options.unzerCustomer
                        );
                        unzerPaymentElement.setCustomerData(
                            this.options.unzerCustomer
                        );
                    }
                }
            );
        }
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
     * @param {String} typeId
     * @param {String} threatMetrixId
     */
    submitTypeId(typeId, threatMetrixId) {
        const resourceIdElement = document.getElementById(
            this.options.resourceIdElementId
        );
        resourceIdElement.value = typeId;

        if (threatMetrixId) {
            const threatMetrixElement = document.getElementById(
                this.options.threatMetrixIdElementId
            );
            if (threatMetrixElement) {
                threatMetrixElement.value = threatMetrixId;
            }
        }

        this.setSubmitButtonActive(true);
        this.submitButton.click();
        this.setSubmitButtonActive(false);
    }

    /**
     * @param {Object} error
     * @param {Boolean} append
     */
    showError(error, append = false) {
        const errorWrapper = document
            .getElementsByClassName(this.options.errorWrapperClass)
            .item(0);
        const errorContent = document.querySelectorAll(
            this.options.errorContentSelector
        )[0];

        if (!append || errorContent.innerText === '') {
            errorContent.innerText = error.message;
        } else {
            errorContent.innerText = `${errorContent.innerText}\n${error.message}`;
        }

        errorWrapper.hidden = false;
        errorWrapper.scrollIntoView({ block: 'end', behavior: 'smooth' });

        this.setSubmitButtonActive(true);
        this.submitting = false;
    }

    /**
     * @param {Object} error
     * @param {HTMLElement} el
     */
    renderErrorToElement(error, el) {
        const errorWrapper = document
            .getElementsByClassName(this.options.errorWrapperClass)
            .item(0);
        const errorContent = document.querySelectorAll(
            this.options.errorContentSelector
        )[0];

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
    async _onSubmitButtonClick(event) {
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

        const selectedSavedDevicesOption = document.querySelector(
            this.options.savedDeviceSelectedRadioButtonSelector
        );
        if (
            selectedSavedDevicesOption &&
            selectedSavedDevicesOption.id !==
                this.options.savedDeviceRadioButtonNewAccountId
        ) {
            //submitting a selected saved device
            this.submitTypeId(selectedSavedDevicesOption.value, null);
        } else {
            const unzerPaymentComponent = document.getElementById(
                'unzer-payment-component'
            );
            if (!unzerPaymentComponent) {
                this.setSubmitButtonActive(true);
                this.submitButton.click();
                this.setSubmitButtonActive(false);
            } else {
                try {
                    const response = await unzerPaymentComponent.submit();

                    if (response.submitResponse) {
                        if (response.submitResponse.success === true) {
                            console.log(
                                'submit response: ',
                                response.submitResponse
                            );
                            this.submitTypeId(
                                response.submitResponse.data.id,
                                response.threatMetrixId || null
                            );
                        } else {
                            this.showError({
                                message: 'GENERAL ERROR',
                            });
                        }
                    } else {
                        this.showError({
                            message: 'EXCEPTIONAL ERROR',
                        });
                    }
                } catch (err) {
                    unzerPaymentComponent.scrollIntoView({
                        block: 'end',
                        behavior: 'smooth',
                    });
                    this.submitting = false;
                    this.setSubmitButtonActive(true);
                }
            }
        }
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
                        message: element.dataset.customError,
                    });
                }

                element.classList.add('is-invalid');

                return false;
            }

            if (element.required && element.value === '') {
                element.classList.add('is-invalid');

                if (element.labels.length === 0 && formValid) {
                    element.scrollIntoView({
                        block: 'end',
                        behavior: 'smooth',
                    });
                } else if (element.labels.length > 0) {
                    this.showError(
                        {
                            message: this.options.errorShouldNotBeEmpty.replace(
                                /%field%/,
                                element.labels[0].innerText
                            ),
                        },
                        true
                    );
                }

                formValid = false;
            } else {
                element.classList.remove('is-invalid');
            }
        }

        return formValid;
    }

    _clearErrorMessage() {
        const errorWrapper = document
            .getElementsByClassName(this.options.errorWrapperClass)
            .item(0);
        const errorContent = document.querySelectorAll(
            this.options.errorContentSelector
        )[0];

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
        const birthDate = !customerInfo.birthday
            ? null
            : new Date(customerInfo.birthday);
        const customerObject = {
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
                country: customerInfo.activeBillingAddress.country.iso,
            },
            shippingAddress: {
                name: combinedName,
                street: customerInfo.activeShippingAddress.street,
                zip: customerInfo.activeShippingAddress.zipcode,
                city: customerInfo.activeShippingAddress.city,
                country: customerInfo.activeShippingAddress.country.iso,
            },
        };

        if (birthDate) {
            // @see https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Date/getMonth
            customerObject.birthDate =
                birthDate.getFullYear() +
                '-' +
                (birthDate.getMonth() + 1).toString().padStart(2, '0') +
                '-' +
                birthDate.getDay().toString().padStart(2, '0');
        }

        return customerObject;
    }
}
