describe('Przelewy24', function () {
    it('Buy with Przelewy24', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Przelewy24 (heidelpay)')

        cy.finishCheckout()
    })
})
