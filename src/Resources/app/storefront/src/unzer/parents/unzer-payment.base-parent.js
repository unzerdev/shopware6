const Plugin = window.PluginBaseClass;

export default class UnzerPaymentBaseParent extends Plugin {
    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @protected
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin =
            window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
    }

    /**
     * @param {Object} error
     *
     * @protected
     */
    _handleError(error) {
        this._unzerPaymentPlugin.showError(error);
    }

    /**
     * @param {Boolean} active
     *
     * @protected
     */
    _setSubmitButtonActive(active) {
        this._unzerPaymentPlugin.setSubmitButtonActive(active);
    }

    _getSubmitButton() {
        return this._unzerPaymentPlugin.getSubmitButton();
    }
}
