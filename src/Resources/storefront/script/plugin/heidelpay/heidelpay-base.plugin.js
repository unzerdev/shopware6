import Plugin from 'src/script/plugin-system/plugin.class';

export default class HeidelpayBasePlugin extends Plugin {
    static options = {
        publicKey: null,
        locale: null,
        submitButtonId: 'confirmFormSubmit',
        disabledClass: 'disabled',
        resourceIdElementId: 'heidelpayResourceId',
        confirmFormId: 'confirmOrderForm'
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static heidelpayInstance = null;

    init() {
        this.heidelpayInstance = new window.heidelpay(this.options.publicKey, {
            locale: this.options.locale
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
    submit(resource) {
        let resourceIdElement = document.getElementById(this.options.resourceIdElementId);
        resourceIdElement.value = resource.id;

        this.confirmForm.submit();
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

        this.$emitter.publish('heidelpayBase_createResource');
    }
}
