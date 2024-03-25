export default Garnish.Modal.extend({
    id: null,
    $form: null,
    $error: null,
    $createButton: null,
    $cancelBtn: null,
    init(orderId, lines, carriers, settings) {
        this.id = Math.floor(Math.random() * 1000000000);

        this.setSettings(settings, {
            resizable: true
        });

        this.$form = $('<form class="modal fitted" method="post" accept-charset="UTF-8" />').appendTo(Garnish.$bod);
        const $body = $('<div class="body" />').appendTo(this.$form);
        const $inputs = $(`<div class="content">
            <input type="hidden" name="orderId" value="${orderId}" />

            <h2 class="first">${Craft.t('order-fulfillments', 'New Fulfillment')}</h2>

            <table class="data fullwidth collapsible">
                <thead>
                    <tr>
                        <th scope="col">${Craft.t('order-fulfillments', 'Item')}</th>
                        <th scope="col">${Craft.t('order-fulfillments', 'Quantity')}</th>
                    </tr>
                </thead>

                <tbody>${this._getRows(lines)}</tbody>
            </table>

            <hr />

            <h3>${Craft.t('order-fulfillments', 'Tracking Information')}</h3>

            <div class="flex">
                <div class="flex-grow">
                    <div class="field">
                        <div class="heading"><label>Tracking Number</label></div>
                        <div class="input ltr">
                            <input class="text fullwidth" type="text" name="trackingNumber" autocomplete="off" />
                        </div>
                    </div>
                </div>

                <div class="flex-grow">
                    <div class="field">
                        <div class="heading"><label>Tracking Carrier</label></div>
                        <div class="input ltr">
                            <div class="select fullwidth">
                                <select class="select" name="trackingCarrierId">${this._getCarriers(carriers)}</select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`).appendTo($body);

        // Error notice area
        this.$error = $('<div class="error" />').appendTo($inputs);

        // Footer and buttons
        const $footer = $('<div class="footer" />').appendTo(this.$form);
        const $mainBtnGroup = $('<div class="buttons right" />').appendTo($footer);
        this.$cancelBtn = $('<input type="button" class="btn" value="' + Craft.t('order-fulfillments', 'Cancel') + '" />').appendTo($mainBtnGroup);
        this.$createButton = $('<input type="button" class="btn submit" value="' + Craft.t('order-fulfillments', 'Create Fulfillment') + '" />').appendTo($mainBtnGroup);

        this.addListener(this.$cancelBtn, 'click', 'hide');
        this.addListener(this.$createButton, 'click', event => {
            event.preventDefault();

            if (!$(event.target).hasClass('disabled')) {
                this.createFulfillment();
            }
        });

        this.base(this.$form, settings);
    },
    createFulfillment() {
        const data = this.$form.serialize();
        this.settings.onSubmit(data);
    },

    _getRows(lines) {
        let html = '';

        for (const line of lines) {
            html += `<tr class="infoRow">
                <td data-title="${Craft.t('order-fulfillments', 'Item')}">${line.title}</td>
                <td data-title="${Craft.t('order-fulfillments', 'Quantity')}">
                    <div class="flex">
                        <input class="text last"
                               type="number"
                               size="4"
                               name="fulfillmentLines[${line.id}]"
                               value="${line.qty}"
                               autocomplete="off"
                               min="0"
                               max="${line.maxQty}" />

                        <span class="flex-grow">
                            ${Craft.t('order-fulfillments', 'of {number}', { number: line.maxQty })}
                        </span>
                    </div>
                </td>
            </tr>`;
        }

        return html;
    },

    _getCarriers(carriers) {
        let html = '';

        for (const carrier of carriers) {
            html += `<option value="${carrier.value}">${carrier.label}</option>`;
        }

        return html;
    },

    defaults: {
        onSubmit: $.noop
    }
});
