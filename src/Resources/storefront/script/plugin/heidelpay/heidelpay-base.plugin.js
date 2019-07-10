import Plugin from 'src/script/plugin-system/plugin.class';

export default class HeidelpayBasePlugin extends Plugin {
    static options = {
        publicKey: null,
        locale : null
    };

    /**
     * @type { Object }
     *
     * @public
     */
    static heidelpayInstance = null;

    init() {
        this.heidelpayInstance = new window.heidelpay(this.options.publicKey, {
            locale: this.options.locale
        });
    }
}
