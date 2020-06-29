describe('SEPA direct debit Test', function () {
    it('Buy with SEPA direct debit', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('SEPA direct debit (heidelpay)')

        cy.get('#heidelpay-sepa-container input').type('DE89370400440532013000');
        cy.get('#acceptSepaMandate').check({force: true});

        cy.finishCheckout()
    })
})
