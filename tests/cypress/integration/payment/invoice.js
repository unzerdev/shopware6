describe('Invoice Test', function () {
    it('Buy with Invoice', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Invoice (heidelpay)')

        cy.finishCheckout()
    })
})
