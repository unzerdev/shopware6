describe('Invoice guaranteed Test', () => {
    it('Buy with Invoice guaranteed', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Unzer invoice guaranteed');

        cy.get('#heidelpayBirthday').type('1990-01-01');

        cy.finishCheckout();
    });
});
