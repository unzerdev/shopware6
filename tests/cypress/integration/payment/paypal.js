describe('PayPal Test', function () {
    it('Buy with PayPal', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('PayPal (heidelpay)')

        cy.url().should('include', 'sandbox.paypal.com');
        cy.get('#email').type('paypal-customer@heidelpay.de');
        cy.get('#password').type('heidelpay');

        cy.finishCheckout()
    })
})
