describe('Prepayment', function () {
    it('Buy with Prepayment', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Prepayment (heidelpay)')

        cy.finishCheckout()
    })
})
