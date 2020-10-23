describe('Prepayment', () => {
    it('Buy with Prepayment', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Prepayment (Unzer payments)');

        cy.finishCheckout();
    });
});
