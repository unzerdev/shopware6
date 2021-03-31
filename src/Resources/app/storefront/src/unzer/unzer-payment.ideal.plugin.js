import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentIdealPlugin extends Plugin {
    /**
     * @type {Object}
     *
     * @public
     */
    static ideal;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.ideal = this._unzerPaymentPlugin.unzerInstance.Ideal();

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        this.ideal.create('ideal', {
            containerId: 'unzer-payment-ideal-container'
        });

        this._unzerPaymentPlugin.setSubmitButtonActive(false);
    }

    /**
     * @private
     */
    _registerEvents() {
        this._unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });

        if (this.ideal) {
            this.ideal.addEventListener('change', (event) => this._onFormChange(event), {
                scope: this
            });
        }
    }

    /**
     * @private
     */
    _onFormChange(event) {
        if (event.value) {
            this._unzerPaymentPlugin.setSubmitButtonActive(true);
        }
    }

    /**
     * @private
     */
    _onCreateResource() {
        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        this.ideal.createResource()
            .then((resource) => this._unzerPaymentPlugin.submitResource(resource))
            .catch((error) => this._unzerPaymentPlugin.showError(error));
    }
}
