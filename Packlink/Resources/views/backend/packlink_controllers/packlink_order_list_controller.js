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

                        if (event.target && event.target.hasAttribute('data-pl-order-id')) {
                            if (event.target.classList.contains('pl-create-draft-button')) {
                                me.createDraft(event.target);
                            } else {
                                me.createDraft(event.target.parentElement);
                            }
                        }
                    },
                    afterrender: function (grid) {
                        let store = grid.getStore();
                        store.on('load', function () {
                            let draftsInProgress = document.getElementsByClassName('pl-draft-in-progress');
                            for (let draftInProgress of draftsInProgress) {
                                if (draftInProgress.hasAttribute('data-pl-order-id')) {
                                    let orderId = draftInProgress.getAttribute('data-pl-order-id'),
                                        parent = draftInProgress.parentElement;

                                    me.checkDraftStatus(parent, orderId);
                                }
                            }
                        });
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
                    },
                }
            }
        )
    },

    createDraft: function (createDraftButton) {
        let me = this,
            parent = createDraftButton.parentElement,
            orderId = createDraftButton.getAttribute('data-pl-order-id');

        parent.removeChild(createDraftButton);
        parent.innerText = 'Draft is currently being created.';

        Packlink.ajaxService.post(
            me.getCreateDraftUrl(),
            {
                orderId: orderId
            },
            function () {
                me.checkDraftStatus(parent, orderId);
            }
        );
    },

    checkDraftStatus: function (parent, orderId) {
        let me = this;

        clearTimeout(function () {
            me.checkDraftStatus(parent, orderId);
        });

        Packlink.ajaxService.get(me.getDraftTaskStatusUrl(orderId), function (response) {
            if (response.status === 'completed') {
                me.displayViewButton(parent, response.shipmentUrl);
            } else if (response.status === 'failed') {
                parent.innerText = 'Previous attempt to create a draft failed.';
                setTimeout(function () {
                    me.displayCreateDraftButton(parent, orderId)
                }, 5000)
            } else if (response.status === 'aborted') {
                parent.innerText = 'Previous attempt to create a draft was aborted.' + ' ' + response.message;
            } else {
                setTimeout(function () {
                    me.checkDraftStatus(parent, orderId)
                }, 1000);
            }
        });
    },

    displayViewButton: function (parent, shipmentUrl) {
        let me = this;
        viewDraftButton = document.createElement('a');

        viewDraftButton.href = shipmentUrl;
        viewDraftButton.target = '_blank';
        viewDraftButton.classList.add('pl-draft-button');
        viewDraftButton.style.display = 'flex';
        viewDraftButton.style.lineHeight = '16px';

        let viewOnPacklink = document.createElement('span');
        viewOnPacklink.innerText = 'View on Packlink';

        viewDraftButton.appendChild(me.getImageElement());
        viewDraftButton.appendChild(viewOnPacklink);

        parent.innerHTML = '';
        parent.appendChild(viewDraftButton);
    },

    displayCreateDraftButton: function (parent, orderId) {
        let me = this;

        clearTimeout(function () {
            displayCreateDraftButton(parent, orderId)
        });

        let createDraftButton = document.createElement('a');

        createDraftButton.classList.add('pl-create-draft-button');
        createDraftButton.setAttribute('data-order-id', orderId);
        createDraftButton.style.display = 'flex';
        createDraftButton.style.lineHeight = '16px';
        createDraftButton.style.cursor = 'pointer';

        let sendWithPacklink = document.createElement('span');
        sendWithPacklink.innerText = 'Send with Packlink';

        createDraftButton.appendChild(me.getImageElement());
        createDraftButton.appendChild(sendWithPacklink);

        createDraftButton.addEventListener('click', function () {
            me.createDraft(createDraftButton);
        });

        parent.innerHTML = '';
        parent.appendChild(createDraftButton);
    },

    getImageElement: function () {
        let img = document.createElement('img');

        img.src = '{link file="backend/_resources/images/logo.png"}';
        img.classList.add('pl-image');
        img.style.width = '16px';

        return img;
    },

    getDraftTaskStatusUrl: function (orderId) {
        let url = '{url controller=PacklinkDraftTaskStatusController action="index"}';
        url += '/index/__csrf_token/' + Ext.CSRFService.getToken();

        return url + '?orderId=' + orderId;
    },

    getCreateDraftUrl: function () {
        return '{url controller=PacklinkDraftTaskCreateController action="create"}' +
            '/__csrf_token/' + Ext.CSRFService.getToken();
    }
});