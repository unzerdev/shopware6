describe('CreditCard Test', function () {
    it('Buy with CreditCard', function () {
        const findInIframe = (selector) => ($iframe) => $iframe.contents().find(selector);

        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Credit card (heidelpay)');

        cy.get('#heidelpay-credit-card-number-input > iframe').pipe(findInIframe('input')).type('4644400000308888', {force: true});
        cy.get('#heidelpay-credit-card-expiry > iframe').pipe(findInIframe('input')).type('12/30', {force: true});
        cy.get('#heidelpay-credit-card-cvc > iframe').pipe(findInIframe('input')).type('123', {force: true});

        cy.finishCheckout();
    })
})
