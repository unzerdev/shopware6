describe('Przelewy24', () => {
    it('Buy with Przelewy24', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Przelewy24 (Unzer payment)');

        cy.finishCheckout();
    });
});
