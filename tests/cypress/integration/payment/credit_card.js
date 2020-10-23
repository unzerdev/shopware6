describe('CreditCard Test', () => {
    it('Buy with CreditCard', () => {
        const findInIframe = (selector) => ($iframe) => $iframe.contents().find(selector);

        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Credit card (Unzer payment)');

        cy.get('#unzer-payment-credit-card-number-input > iframe').pipe(findInIframe('input')).type('4644400000308888', { force: true });
        cy.get('#unzer-payment-credit-card-expiry > iframe').pipe(findInIframe('input')).type('12/30', { force: true });
        cy.get('#unzer-payment-credit-card-cvc > iframe').pipe(findInIframe('input')).type('123', { force: true });

        cy.finishCheckout();
    });
});
