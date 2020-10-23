describe('Flexipay Test', () => {
    it('Buy with Flexipay', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('FlexiPay® Rate (Unzer payments)');

        cy.finishCheckout();
    });
});
