// Packlink order list controller
// {namespace name=backend/packlink/configuration}

Ext.define('Shopware.apps.Packlink.controller.OrderListController', {
    /**
     * Override the order list main controller
     * @string
     */
    override: 'Shopware.apps.Order.controller.List',

    init: function () {
        let me = this;

        // me.callParent will execute the init function of the overridden controller
        me.callParent(arguments);

        me.control(
            {
                'order-list': {
                    cellclick: function (grid, cell, index, record, row, rowIndex, event) {
                        if (event.target && event.target.hasAttribute('data-pl-label') && record.get('plHasLabel')) {
                            let url = '{url controller=PacklinkPrintLabelsController action="print"}';
                            url += '/__csrf_token/' + Ext.CSRFService.getToken();
                            url += '?orderIds=' + record.get('id');
                            let newWindow = window.open(url, '_blank');
                            newWindow.focus();
                            event.target.classList.remove('sprite-tag');
                            event.target.classList.add('sprite-tag-label-black');
                        }
                    },
                    plPrintLabels: function (grid) {
                       if (grid.plOrderCheckboxes) {
                           let selected = grid.plOrderCheckboxes.getSelection();

                           selected = selected.filter(function (order) {
                               return order.get('plHasLabel');
                           });

                           let ids = selected.map(function (order) {
                               return order.get('id');
                           });

                           if (ids.length !== 0) {
                               printLabels(ids);
                               setPrinted(ids);
                           }
                       }

                       function printLabels(ids) {
                           let url = '{url controller=PacklinkPrintLabelsController action="print"}';
                           url += '/__csrf_token/' + Ext.CSRFService.getToken();
                           url += '?orderIds=' + ids.join(',');
                           let newWindow = window.open(url, '_blank');
                           newWindow.focus();
                       }

                       function setPrinted(ids) {
                           let element = grid.getEl();
                           let icons = element.query('[data-pl-label]');

                           for (let icon of icons) {
                               if (ids.indexOf(parseInt(icon.getAttribute('data-pl-label'))) !== -1) {
                                   icon.classList.remove('sprite-tag');
                                   icon.classList.add('sprite-tag-label-black');
                               }
                           }
                       }
                    }
                }
            }
        )
    }
});