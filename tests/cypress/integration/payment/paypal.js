describe('PayPal Test', () => {
    it('Buy with PayPal', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('PayPal (Unzer payment)');

        cy.url().should('include', 'sandbox.paypal.com');
        cy.get('#email').type('paypal-customer@heidelpay.de');
        cy.get('#password').type('heidelpay');

        cy.finishCheckout();
    });
});
