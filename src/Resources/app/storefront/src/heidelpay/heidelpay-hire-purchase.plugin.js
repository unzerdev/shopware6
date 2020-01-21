import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
export default class HeidelpayHirePurchasePlugin extends Plugin {
    static options = {
        hirePurchaseAmount: 0.0,
        hirePurchaseCurrency: '',
        hirePurchaseEffectiveInterest: 0.0,
        hirePurchaseOrderDate: '',
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static hirePurchase;

    /**
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static heidelpayPlugin = null;

    init() {
        this.heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];
        this.hirePurchase = this.heidelpayPlugin.heidelpayInstance.HirePurchase();
        this.heidelpayPlugin.setSubmitButtonActive(false);

        this._createForm();
        this._registerEvents();
    }

    _createForm() {
        ElementLoadingIndicatorUtil.create(this.el.firstElementChild);

        this.hirePurchase.create({
            containerId: 'heidelpay-hire-purchase-container',
            amount: this.options.hirePurchaseAmount.toFixed(4),
            currency: this.options.hirePurchaseCurrency,
            effectiveInterest: this.options.hirePurchaseEffectiveInterest,
            orderDate: this.options.hirePurchaseOrderDate,
        }).then(() => {
            this.el.firstElementChild.hidden = true;
        }).catch((error) => {
            this.heidelpayPlugin.renderErrorToElement(error, this.el.firstElementChild);
            this.heidelpayPlugin.setSubmitButtonActive(false);
        }).finally(() => {
            ElementLoadingIndicatorUtil.remove(this.el.firstElementChild);

        });
    }

    _registerEvents() {
        this.heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this,
        });

        this.hirePurchase.addEventListener('hirePurchaseEvent', (event) => this._onChangeHirePurchaseForm(event));
    }

    _onCreateResource() {
        this.heidelpayPlugin.setSubmitButtonActive(false);

        this.hirePurchase.createResource()
            .then((resource) => this.heidelpayPlugin.submitResource(resource))
            .catch((error) => this.heidelpayPlugin.showError(error));
    }

    _onChangeHirePurchaseForm(event) {
        if (event.action === 'validate') {
            if (event.success) {
                this.heidelpayPlugin.setSubmitButtonActive(true);
            } else {
                this.heidelpayPlugin.setSubmitButtonActive(false);
            }
        }
    }
}
