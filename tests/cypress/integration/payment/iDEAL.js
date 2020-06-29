describe('iDEAL Test', function () {
    it('Buy with iDEAL', function () {
        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('iDEAL (heidelpay)')

        cy.get('#heidelpay-ideal-container .heidelpayChoices__list--single').click();
        cy.get('.heidelpayChoices__input heidelpayChoices__input--cloned').type('Test');
        cy.get('.heidelpayChoices__list div:first-child').click();

        cy.finishCheckout()
    })
})
