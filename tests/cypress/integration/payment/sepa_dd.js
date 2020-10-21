describe('SEPA direct debit Test', () => {
    it('Buy with SEPA direct debit', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Unzer direct debit');

        cy.get('#unzer-sepa-container input').type('DE89370400440532013000');
        cy.get('#acceptSepaMandate').check({ force: true });

        cy.finishCheckout();
    });
});
