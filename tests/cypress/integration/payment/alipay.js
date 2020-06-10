describe('Alipay', function () {
    it('Buy with Alipay', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Alipay (heidelpay)')

        cy.finishCheckout()
    })
})
