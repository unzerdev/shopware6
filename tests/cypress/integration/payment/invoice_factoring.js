describe('Invoice factoring Test', () => {
    it('Buy with Invoice factoring', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Unzer invoice factoring');

        cy.get('#heidelpayBirthday').type('1990-01-01');

        cy.finishCheckout();
    });
});
