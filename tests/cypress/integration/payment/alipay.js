describe('Alipay', () => {
    it('Buy with Alipay', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Alipay (Unzer payment)');

        cy.finishCheckout();
    });
});
