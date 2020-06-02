describe('Flexipay Test', function () {
    it('Buy with Flexipay', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Flexipay (heidelpay)')

        cy.finishCheckout()
    })
})
