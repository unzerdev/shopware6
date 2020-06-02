Cypress.Commands.add('register', () => {
    cy.url().should('include', 'checkout/register')
    cy.get('#personalSalutation').select('Mr.').should('contain.text', 'Mr.');

    cy.get('#personalFirstName').type('Heidelpay');
    cy.get('#personalLastName').type('Testkäufer');

    cy.get('#personalMail').type('demo@heidelpay.demo');
    cy.get('#personalGuest').check({force: true});

    cy.get('#billingAddressAddressStreet').type('Vangerowstraße 18');
    cy.get('#billingAddressAddressZipcode').type('69115');
    cy.get('#billingAddressAddressCity').type('Heidelberg');

    cy.get('#billingAddressAddressCountry').select('Germany').should('contain.text', 'Germany');
    cy.get('.register-submit > button').click();
})
