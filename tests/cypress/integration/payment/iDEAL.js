describe('iDEAL Test', () => {
    it('Buy with iDEAL', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('iDEAL (Unzer payment)');

        cy.get('#unzerp-payment-ideal-container .heidelpayChoices__list--single').click();
        cy.get('.heidelpayChoices__input heidelpayChoices__input--cloned').type('Test');
        cy.get('.heidelpayChoices__list div:first-child').click();

        cy.finishCheckout();
    });
});
