describe('SEPA direct debit guaranteed', function () {
    it('Buy with SEPA direct debit guaranteed', function () {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('SEPA direct debit guaranteed (heidelpay)');

        cy.get('#heidelpayBirthday').type('1990-01-01');
        cy.get('#heidelpay-sepa-container input').type('DE89370400440532013000');
        cy.get('#acceptSepaMandate').check({force: true});

        cy.finishCheckout()
    })
})
