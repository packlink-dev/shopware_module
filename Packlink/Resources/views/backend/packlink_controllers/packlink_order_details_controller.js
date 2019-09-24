// Packlink order details controller
// {namespace name=backend/packlink/configuration}

Ext.define('Shopware.apps.Packlink.controller.OrderDetailsController', {
    /**
     * Override the order details main controller
     * @string
     */
    override: 'Shopware.apps.Order.controller.Detail',

    init: function () {
        let me = this;

        // me.callParent will execute the init function of the overridden controller
        me.callParent(arguments);

        me.control(
            {
                'packlink_order_details': {
                    activate: function (tab) {
                        tab.taskRunner = new Ext.util.TaskRunner();
                        me.packlinkBootstrap(tab);
                    },
                    deactivate: function (tab) {
                        me.packlinkDestroy(tab.taskRunner);
                    },
                    remove: function (tab) {
                        me.packlinkDestroy(tab.taskRunner);
                    },
                    destroy: function (tab) {
                        me.packlinkDestroy(tab.taskRunner);
                    }
                }
            }
        )
    },
    packlinkBootstrap: function (packlinkTab) {
        let ajaxService = Packlink.ajaxService;
        let tab = packlinkTab;
        let taskRunner = tab.taskRunner;
        let order = tab.record.data;
        let currentState = null;
        let stateCleanupCallbacks = [];
        let store = tab.record.store;

        init();
        function init() {
            tab.removeAll();
            tab.setLoading(true);
            ajaxService.get(getDraftTaskStatusUrl(order.id), getDraftTaskStatusSuccessHandler);
        }

        function getDraftTaskStatusSuccessHandler(response) {
            tab.setLoading(false);
            let status = response.status;

            switch (status) {
                case 'not_logged_in':
                    start(notLoggedInStateHandler(), 'not_logged_in');
                    break;
                case 'not_created':
                    start(notCreatedStateHandler(), 'not_created');
                    break;
                case 'in_progress':
                    start(inProgressStateHandler(), 'in_progress');
                    break;
                case 'completed':
                    start(completedStateHandler(), 'completed');
                    break;
                default:
                    start(notCreatedStateHandler(), 'not_created');
                    break;
            }
        }

        /**
         * Retrieves send draft task status url.
         *
         * @param { number } orderId
         * @return { string }
         */
        function getDraftTaskStatusUrl(orderId) {
            let url = '{url controller=PacklinkDraftTaskStatusController action="index"}';

            return url + '?orderId=' + orderId;
        }

        /**
         * Starts new state.
         *
         * @param { object } handler
         * @param { string } state
         */
        function start(handler, state) {
            if (currentState === state) {
                return;
            }

            for (let callback of stateCleanupCallbacks) {
                callback();
            }

            currentState = state;
            stateCleanupCallbacks = handler.getCleanupCallbacks();
            tab.removeAll();
            handler.handle();
        }

        /**
         * Handles not created state.
         *
         * @return { object }
         */
        function notCreatedStateHandler() {
            let task = null;
            this.handle = function () {
                render();
                task = createStatusCheckerTask();
                task.start();
            };

            function render() {
                let panel = createShipmentPanel('{s name="shipment/details/tab/title"}Shipment details{/s}', getPanelItems);
                tab.add(panel);
            }

            function getPanelItems() {
                return [
                    Ext.create('Ext.Button', {
                        text: '{s name="shipment/create/draft/label"}Create shipment draft on Packlink PRO{/s}',
                        cls: 'large primary',
                        style: {
                            margin: '10px'
                        },
                        handler: onCreateDraftClicked
                    })
                ]
            }

            function onCreateDraftClicked() {
                ajaxService.post(getCreateDraftUrl(), { orderId: order.id }, createDraftSuccessHandler);
            }

            function getCreateDraftUrl() {
                return '{url controller=PacklinkDraftTaskCreateController action="create"}'
            }

            function createDraftSuccessHandler() {
                if (currentState !== 'not_created') {
                    return;
                }

                start(inProgressStateHandler(), 'in_progress');
            }

            this.getCleanupCallbacks = function () {
                return [
                    function () {
                        if (task) {
                            task.stop();
                        }
                    }
                ];
            };

            return this;
        }

        /**
         * Returns in progress state handler.
         *
         * @return { object }
         */
        function inProgressStateHandler() {
            let task = null;

            this.handle = function () {
                render();
                task = createStatusCheckerTask();
                task.start();
            };

            this.getCleanupCallbacks = function () {
                return [
                    function () {
                        if (task) {
                            task.stop();
                        }
                    }
                ];
            };

            function render() {
                tab.add(createShipmentPanel('{s name="shipment/details/tab/title"}Shipment details{/s}', getPanelItems))
            }

            function getPanelItems() {
                return [
                    {
                        xtype: 'displayfield',
                        value: '{s name="shipment/inprogress/label"}Draft is currently being created in Packlink PRO{/s}',
                        style: {
                            margin: '10px'
                        },
                    }
                ]
            }

            return this;
        }

        /**
         * Returns not logged in state handler.
         *
         * @return { object }
         */
        function notLoggedInStateHandler() {
            let task = null;

            this.handle = function () {
                render();
                task = createStatusCheckerTask();
                task.start();
            };

            this.getCleanupCallbacks = function () {
                return [
                    function () {
                        if (task) {
                            task.stop();
                        }
                    }
                ];
            };

            function render() {
                tab.add(createShipmentPanel('{s name="shipment/details/tab/title"}Shipment details{/s}', getPanelItems))
            }

            function getPanelItems() {
                return [
                    {
                        xtype: 'displayfield',
                        value: '{s name="shipment/please/login"}Please login to see shipment details{/s}',
                        style: {
                            margin: '10px'
                        },
                    }
                ]
            }

            return this;
        }

        /**
         * Completed state handler.
         *
         * @return { object }
         */
        function completedStateHandler() {
            let task;
            
            this.handle = function () {
                tab.setLoading(true);
                getDraftDetails();
                task = createRefreshDetailsTask();
                task.start();
            };
            
            this.getCleanupCallbacks = function () {
                return [
                    function () {
                        if (task) {
                            task.stop();
                        }
                    }
                ];
            };

            /**
             * Retrieves draft details.
             */
            function getDraftDetails() {
                ajaxService.get(getDraftDetailsUrl(order.id), render);
            }

            /**
             * Retrieves url for retrieving draft details.
             *
             * @param { number } id
             * @return { string }
             */
            function getDraftDetailsUrl(id) {
                return '{url controller=PacklinkDraftDetailsController action="index"}?orderId=' + id;
            }

            /**
             * Renders draft details page.
             *
             * @param { object } response
             */
            function render(response) {
                tab.removeAll();
                tab.add(getPanels(response));
                tab.setLoading(false);
                store.reload();
            }

            /**
             * Retrieves panels for draft details page.
             *
             * @param { object } data
             *
             * @return { Array }
             */
            function getPanels(data) {
                return [
                    createShipmentPanel(
                        '{s name="shipment/details/tab/title"}Shipment details{/s}',
                        function () {
                            return getLeftPanel(data);
                        },
                        function () {
                            return getLeftPanelToolbar(data);
                        }
                    ),
                    createShipmentPanel(
                        '{s name="shipment/status/title"}Shipment status{/s}',
                        function () {
                            return getRightPanel(data);
                        },
                        function () {
                            return getRightPanelToolbar(data);
                        }
                    )
                ]
            }

            /**
             * Retrieves left panel items.
             *
             * @param { object } data
             * @return { Array }
             */
            function getLeftPanel(data) {
                let text =  [
                    data.carrier || '',
                    '{s name="shipment/total/charges"}Total shipping charges (EUR):{/s} ' + (data.orderCost || 'n/a'),
                    '{s name="shipment/reference/number"}Packlink reference number:{/s} ' + (data.reference || 'n/a'),
                    '{s name="shipment/packlink/price"}Packlink shipping price (EUR):{/s} ' + (data.cost || 'n/a'),
                ];

                let subpanel = Ext.create('Ext.panel.Panel', {

                    border: false,
                    bodyBorder: false,
                    height: 150,
                    flex: 1,
                    width: '100%',
                    defaults: {
                        xtype: 'displayfield',
                        flex: 1,
                        width: '100%',
                        style: {
                            marginLeft: '5px'
                        }
                    },
                    items: text.map(function (item) {
                        return { value: item }
                    })
                });

                return  [subpanel];
            }

            /**
             * Retrieves left panel toolbar.
             *
             * @param data
             * @return { Ext.toolbar.Toolbar }
             */
            function getLeftPanelToolbar(data) {
                let items = [getViewOnPacklinkButton()];
                if (data.isLabelsAvailable) {
                    items.push(getPrintLabelsButton());
                }

                return Ext.create('Ext.toolbar.Toolbar', {
                    items: items,
                    border: 1,
                    style: {
                        borderColor: '#a4b5c0',
                        borderStyle: 'solid'
                    }
                });

                /**
                 * Creates view on packlink button.
                 *
                 * @return { Ext.Button }
                 */
                function getViewOnPacklinkButton() {
                    return Ext.create('Ext.Button', {
                        text: '{s name="shipment/view"}View on Packlink PRO{/s}',
                        cls: 'large primary',
                        disabled: !data.referenceUrl,
                        border: true,
                        handler: onViewOnPacklinkClicked
                    });

                    /**
                     * Click handler for view on packlink button.
                     */
                    function onViewOnPacklinkClicked() {
                        openNewTab(data.referenceUrl)
                    }
                }

                /**
                 * Creates print labels button.
                 *
                 * @return { Ext.Button }
                 */
                function getPrintLabelsButton() {
                    let printButton = Ext.create('Ext.Button', {
                        text: '{s name="shipment/printed"}Printed{/s}',
                        cls: 'large secondary',
                        border: true,
                        handler: onPrintLabelsButtonClicked
                    });

                    if (!data.isPrinted) {
                        printButton.cls = 'large primary';
                        printButton.text = '{s name="shipment/print"}Print shipment labels{/s}'
                    }

                    return printButton;
                }

                /**
                 * Click handler for print labels button.
                 */
                function onPrintLabelsButtonClicked() {
                    let url = '{url controller=PacklinkPrintLabelsController action="print"}';
                    url += '?orderIds=' + tab.record.get('id');
                    openNewTab(url);
                    store.reload();
                }
            }

            /**
             * Retrieves right panel.
             *
             * @param { object }data
             * @return { Array }
             */
            function getRightPanel(data) {
                let rightColumn = Ext.create('Ext.panel.Panel', {
                    border: false,
                    bodyBorder: false,
                    layout: 'vbox',
                    flex: 1,
                });

                let items = [rightColumn];

                let rightColumnItems = [];

                if (data.shippingMethod) {
                    rightColumnItems.push({
                        xtype: 'displayfield',
                        value: data.shippingMethod
                    });

                    items.push(Ext.create('Ext.Img', {
                        src: data.logo,
                        width: 96,
                    }));
                }

                rightColumnItems.push(...getAdditionalInformation());
                rightColumn.add(rightColumnItems);

                let subpanel = Ext.create('Ext.panel.Panel', {
                    layout: {
                        type: 'hbox'
                    },
                    items: items,
                    border: false,
                    bodyBorder: false,
                    height: 150,
                    bodyPadding: 5,
                    defaults: {
                        style: {
                            marginTop: '5px'
                        }
                    }
                });

                return [subpanel];

                /**
                 * Retrieves additional information.
                 *
                 * @return { Array }
                 */
                function getAdditionalInformation() {
                    return [
                            {
                                xtype: 'displayfield',
                                value: '{s name="shipment/status"}Status:{/s} ' + (data.status || 'n/a'),
                            },
                            {
                                xtype: 'displayfield',
                                value: '{s name="shipment/tracking/numbers"}Tracking numbers:{/s} ' + (data.trackingNumbers || 'n/a'),
                            }
                        ];
                }
            }

            /**
             * Retrieves right panel toolbar.
             *
             * @param data
             *
             * @return { Ext.toolbar.Toolbar }
             */
            function getRightPanelToolbar(data) {
                return Ext.create('Ext.toolbar.Toolbar', {
                    items: [getTrackButton()],
                    border: 1,
                    style: {
                        borderColor: '#a4b5c0',
                        borderStyle: 'solid'
                    }
                });

                function getTrackButton() {
                    return Ext.create('Ext.Button', {
                        text: '{s name="shipment/trackit"}Track it{/s}',
                        cls: 'large primary',
                        border: true,
                        disabled: !data.trackingUrl,
                        handler: onTrackButtonClicked
                    });

                    function onTrackButtonClicked() {
                        openNewTab(data.trackingUrl);
                    }
                }
            }

            /**
             * Retrieves task that refreshes draft details.
             *
             * @return { task }
             */
            function createRefreshDetailsTask() {
                return taskRunner.newTask({
                    run: function() {
                        getDraftDetails();
                    },
                    interval: 5000
                })
            }
            
            return this;
        }

        /**
         * Creates empty shipment panel.
         *
         * @param { string } title
         * @param { function } getPanelItems
         * @param { function } [getBbarItems]
         * @return { Ext.panel.Panel }
         */
        function createShipmentPanel(title, getPanelItems, getBbarItems) {
            let config = {
                title: title,
                align: 'stretch',
                items: getPanelItems()
            };

            if (typeof getBbarItems !== 'undefined') {
                config.bbar = getBbarItems();
            }

            return Ext.create('Ext.container.Container', {
                columnWidth: 0.5,
                padding: 10,
                items: [Ext.create('Ext.panel.Panel', config)]
            });
        }

        /**
         * Creates task that periodically retrieves status of draft.
         *
         * @return { task }
         */
        function createStatusCheckerTask() {
            return taskRunner.newTask({
                run: function() {
                    ajaxService.get(getDraftTaskStatusUrl(order.id), getDraftTaskStatusSuccessHandler);
                },
                interval: 1000
            })
        }

        /**
         * Opens url in new tab and focuses to it.
         *
         * @param { string } url
         */
        function openNewTab(url) {
            let win = window.open(url, '_blank');
            win.focus();
        }
    },
    packlinkDestroy: function (taskRunner) {
        if (taskRunner) {
            taskRunner.stopAll();
        }
    }
});