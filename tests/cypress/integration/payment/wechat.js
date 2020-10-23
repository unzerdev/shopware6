describe('WeChat', () => {
    it('Buy with WeChat', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('WeChat (Unzer payment)');

        cy.finishCheckout();
    });
});
