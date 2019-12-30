import CreateFulfillmentModal from './CreateFulfillmentModal';

if (typeof Craft.Fulfillments === typeof undefined) {
    Craft.Fulfillments = {};
}

Craft.Fulfillments.CreateFulfillment = Garnish.Base.extend({
    orderId: null,
    fulfillmentModal: null,

    init(settings) {
        this.setSettings(settings);
        this.orderId = this.settings.orderId;

        this.$fulfillment = $('#new-fulfillment');

        this.$fulfillment.toggleClass('hidden');
        this.addListener(this.$fulfillment.find('.newfulfillment'), 'click', ev => {
            ev.preventDefault();
            this._openCreateFulfillmentModal();
        });
    },

    _openCreateFulfillmentModal() {
        const lines = this.$fulfillment.find('.newfulfillment').data('lines');
        const carriers = this.$fulfillment.find('.newfulfillment').data('carriers');

        this.fulfillmentModal = new CreateFulfillmentModal(this.orderId, lines, carriers, {
            onSubmit: data => {
                Craft.postActionRequest('order-fulfillments/fulfillments/save', data, response => {
                    if (response.success) {
                        Craft.cp.displayNotice(Craft.t('order-fulfillments', 'Fulfillment Created.'));
                        this.fulfillmentModal.hide();

                        location.reload();
                    } else {
                        alert(response.error);
                    }
                });
            }
        });
    }
}, {
    defaults: {
        orderId: null,
    }
});
