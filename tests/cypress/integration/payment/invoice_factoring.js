describe('Invoice factoring Test', function () {
    it('Buy with Invoice factoring', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Invoice factoring (heidelpay)')

        cy.get('#heidelpayBirthday').type('1990-01-01');

        cy.finishCheckout()
    })
})
