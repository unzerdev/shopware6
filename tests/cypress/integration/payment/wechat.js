describe('WeChat', function () {
    it('Buy with WeChat', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('WeChat (heidelpay)')

        cy.finishCheckout()
    })
})
