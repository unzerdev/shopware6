import Plugin from 'src/script/plugin-system/plugin.class';

export default class HeidelpayBasePlugin extends Plugin {
    static options = {
        publicKey: null,
        locale: null,
        submitButtonId: 'confirmFormSubmit',
        disabledClass: 'disabled',
        resourceIdElementId: 'heidelpayResourceId',
        confirmFormId: 'confirmOrderForm',
        errorWrapperClass: 'heidelpay-error-wrapper',
        errorContentSelector: '.heidelpay-error-wrapper .alert-content',
        errorShouldNotBeEmpty: '%field% should not be empty',
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static heidelpayInstance = null;

    init() {
        this.heidelpayInstance = new window.heidelpay(this.options.publicKey, {
            locale: this.options.locale,
        });

        this.submitButton = document.getElementById(this.options.submitButtonId);
        this.confirmForm = document.getElementById(this.options.confirmFormId);

        this._registerEvents();
    }

    /**
     * @param {Boolean} active
     *
     * @public
     */
    setSubmitButtonActive(active) {
        if (active) {
            this.submitButton.classList.remove(this.options.disabledClass);
        } else {
            this.submitButton.classList.add(this.options.disabledClass);
        }
    }

    /**
     * @param {Object} resource
     */
    submitResource(resource) {
        const resourceIdElement = document.getElementById(this.options.resourceIdElementId);
        resourceIdElement.value = resource.id;

        this.confirmForm.submit();
    }

    submitTypeId(typeId) {
        const resourceIdElement = document.getElementById(this.options.resourceIdElementId);
        resourceIdElement.value = typeId;

        this.confirmForm.submit();
    }

    /**
     * @param { Object } error
     */
    showError(error) {
        const errorWrapper = document.getElementsByClassName(this.options.errorWrapperClass).item(0),
            errorContent = document.querySelectorAll(this.options.errorContentSelector)[0];

        errorContent.innerText = error.message;

        errorWrapper.hidden = false;
        errorWrapper.scrollIntoView({ block: 'end', behavior: 'smooth' });

        this.setSubmitButtonActive(true);
    }

    /**
     * @private
     */
    _registerEvents() {
        this.submitButton.addEventListener('click', this._onSubmitButtonClick.bind(this));
    }

    /**
     * @private
     */
    _onSubmitButtonClick(event) {
        event.preventDefault();

        if (!this._validateForm()) {
            return;
        }

        this.setSubmitButtonActive(false);
        this.$emitter.publish('heidelpayBase_createResource');
    }

    /**
     * @return {boolean}
     * @private
     */
    _validateForm() {
        const form = document.forms[this.options.confirmFormId].elements;

        for (let i = 0; i < form.length; i++) {
            const element = form[i];

            if (element.required && element.value === '') {
                element.classList.add('is-invalid');

                if (element.labels.length === 0) {
                    element.scrollIntoView({ block: 'end', behavior: 'smooth' });

                    return false;
                }

                this.showError({
                    message: this.options.errorShouldNotBeEmpty.replace(/%field%/, element.labels[0].innerText),
                });

                return false;
            }
        }

        return true;
    }
}
