describe('Giropay', function () {
    it('Buy with Giropay', function () {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Giropay (heidelpay)');


        cy.url().should('include', 'customer-integration.giropay');
        cy.get('#tags').type('TESTDETT421');
        cy.get('.ui-menu-item a:first-child').click();
        cy.get('#idtoGiropayDiv input').click();
        cy.wait(2000);
        cy.get('#no').click();
        cy.wait(2000);
        cy.get('[name="account/addition[@name=benutzerkennung]"]').type('chiptanscatest2');
        cy.get('[name="ticket/pin"]').type('12345');
        cy.get('button[type="submit"]').should('contain.text', 'Jetzt bezahlen').click();
        cy.wait(2000);
        cy.get('[name="weiterButton"]').click();
        cy.wait(2000);
        cy.get('[name="ticket/tan"]').type('123456');
        cy.get('button[type="submit"]').should('contain.text', 'Login').click();
        cy.wait(2000);
        cy.get('button[type="submit"]').should('contain.text', 'Weiter').click();
        cy.wait(2000);
        cy.get('[name="ticket/tan"]').type('123456');
        cy.get('[name="BezahlButton"]').should('contain.text', 'Jetzt bezahlen').click();
        cy.wait(5000);
        cy.url().should('include', 'checkout/finish');
        cy.finishCheckout();
    });
});
