describe('Invoice guaranteed Test', function () {
    it('Buy with Invoice guaranteed', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Invoice guaranteed (heidelpay)')

        cy.get('#heidelpayBirthday').type('1990-01-01');

        cy.finishCheckout()
    })
})
