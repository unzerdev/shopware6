describe('Sofort Test', () => {
    it('Buy with Sofort', () => {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Sofort (Unzer payment)');

        cy.get('#tos').check({ force: true });
        cy.get('#confirmFormSubmit').click({ force: true });

        // Needs some cookies
        // cy.url().should('include', 'www.sofort.com/payment');
    });
});
