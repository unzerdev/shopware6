import { Component } from 'src/core/shopware';
import template from './sw-order.html.twig';

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isHeidelpayPayment: true
        };
    },

    computed: {
        showTabs() {
            return true; // TODO remove with PT-10455
        },

        paymentMethodStore() {
            return State.getStore('payment_method');
        },
    },

    created() {
        // ToDo with NEXT-3911: Remove this Quickfix
        this.$router.push({ name: 'sw.order.detail', params: { id: this.orderId } });
    },

    // watch: {
    //     orderId: {
    //         deep: true,
    //         handler() {
    //             if (!this.orderId) {
    //                 this.setIsPayPalPayment(null);
    //                 return;
    //             }
    //
    //             const orderRepository = this.repositoryFactory.create('order');
    //             const orderCriteria = new Criteria(1, 1);
    //             orderCriteria.addAssociation('transactions');
    //
    //             orderRepository.get(this.orderId, this.context, orderCriteria).then((order) => {
    //                 if (order.transactions.length <= 0 ||
    //                     !order.transactions[0].paymentMethodId
    //                 ) {
    //                     this.setIsPayPalPayment(null);
    //                     return;
    //                 }
    //
    //                 const paymentMethodId = order.transactions[0].paymentMethodId;
    //
    //                 if (paymentMethodId !== undefined && paymentMethodId !== null) {
    //                     this.setIsPayPalPayment(paymentMethodId);
    //                 }
    //             });
    //         },
    //         immediate: true
    //     }
    // },
    //
    // methods: {
    //     setIsHeidelpayPayment(paymentMethodId) {
    //         if (!paymentMethodId) {
    //             return;
    //         }
    //
    //         this.paymentMethodStore.getByIdAsync(paymentMethodId).then(
    //             (paymentMethod) => {
    //                 this.isPayPalPayment = paymentMethod.formattedHandlerIdentifier === paypalFormattedHandlerIdentifier;
    //             }
    //         );
    //     }
    // }
});
