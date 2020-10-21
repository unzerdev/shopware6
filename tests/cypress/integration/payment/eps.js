describe('EPS Test', () => {
    it('Buy with EPS', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('EPS (Unzer payment)');


        cy.get('#unzer-payment-eps-container .heidelpayChoices__inner').click();
        cy.get('.heidelpayChoices__list > input').type('Erste Bank und Sparkassen');
        cy.get('.heidelpayChoices__list > .heidelpayChoices__list div:first-child').click();

        cy.get('#tos').check({ force: true });
        cy.get('#confirmFormSubmit').click({ force: true });
        cy.url().should('include', 'payment.heidelpay.com/');
    });
});
