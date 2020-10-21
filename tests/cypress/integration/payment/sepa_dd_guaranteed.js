describe('SEPA direct debit guaranteed', () => {
    it('Buy with SEPA direct debit guaranteed', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('SEPA direct debit guaranteed (Unzer payment)');

        cy.get('#unzerPaymentBirthday').type('1990-01-01');
        cy.get('#unzer-payment-sepa-container input').type('DE89370400440532013000');
        cy.get('#acceptSepaMandate').check({ force: true });

        cy.finishCheckout();
    });
});
