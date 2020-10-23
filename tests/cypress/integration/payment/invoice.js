describe('Invoice Test', () => {
    it('Buy with Invoice', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Invoice (Unzer payment)');

        cy.finishCheckout();
    });
});
