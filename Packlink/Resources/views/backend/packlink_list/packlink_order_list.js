// {namespace name=backend/packlink/configuration}

//{block name="backend/order/view/list/list"}
// {$smarty.block.parent}

Ext.define('Shopware.apps.Packlink.view.Order.List', {
    override: 'Shopware.apps.Order.view.list.List',
    getColumns: function () {
        let result = this.callParent();

        result = this.addPacklinkColumns(result);
        
        return result;
    },
    registerEvents: function () {
        this.callParent();
        this.addEvents(
            'plPrintLabels'
        )
    },
    getGridSelModel: function () {
        let me = this;
        me.plOrderCheckboxes = me.callParent();

        me.plOrderCheckboxes.addListener('selectionchange', function (sm, selections) {
           if (me.plPrintLabelsBtn !== null) {
               me.plPrintLabelsBtn.setDisabled(!(selections.length));
           }
        });

        return me.plOrderCheckboxes;
    },
    getToolbar: function() {
        let me = this;
        let toolbar = me.callParent();

        me.plPrintLabelsBtn = Ext.create('Ext.button.Button', {
            text: '{s name="order/print/labels/btn"}Print Packlink PRO shipping labels{/s}',
            disabled:true,
            handler: function() {
                me.fireEvent('plPrintLabels', me);
            }
        });

        toolbar.insert(1, me.plPrintLabelsBtn);

        return toolbar;
    },
    addPacklinkColumns: function (columns) {
        let i = 0;
        for (i; i < columns.length; i++) {
            if (columns[i].dataIndex === 'dispatchId'){
                break;
            }
        }

        columns.splice(++i, 0, {
            header: '{s name="order/packlink/pro"}Packlink PRO{/s}',
            dataIndex: 'plReferenceUrl',
            flex: 4,
            sortable: false,
            renderer: renderPacklinkProColumn
        });

        columns.splice(columns.length - 1, 0, {
            header: '{s name="order/print/labels"}Labels{/s}',
            dataIndex: 'plHasLabel',
            flex: 1,
            sortable: false,
            renderer: renderPrintLabelsColumn
        });

        return columns;

        function renderPacklinkProColumn(value, meta, model) {
            switch (model.get('plDraftStatus')) {
                case 'completed':
                    return '<a class="pl-draft-button" href="' + value + '"'
                        + (model.get('plIsDeleted') ? ' disabled' : ' target="_blank"')
                        + ' style="display: flex; line-height: 16px;">'
                        + '<img class="pl-image" width="16px" height="16px" src="{link file="backend/_resources/images/logo.png"}" />'
                        + '<span>{s name="shipment/view"}View on Packlink{/s}</span></a>';
                case 'in_progress':
                case 'queued':
                    return '<span class="pl-draft-in-progress" data-pl-order-id="' + model.get('id') + '">'
                        + '{s name="shipment/inprogress/label"}Draft is currently being created.{/s}'
                        + '</span>';
                case 'aborted':
                    return '{s name="shipment/aborted/label"}Previous attempt to create a draft was aborted.{/s}' + ' ' + model.get('plMessage');
                default:
                    return '<a class="pl-create-draft-button" data-pl-order-id="' + model.get('id') + '"'
                        + ' style="display: flex; line-height: 16px; cursor: pointer;">'
                        + '<img class="pl-image" data-pl-order-id="' + model.get('id')
                        + '" width="16px" height="16px" src="{link file="backend/_resources/images/logo.png"}" />'
                        + '<span data-pl-order-id="' + model.get('id') + '">{s name="shipment/send"}Send with Packlink{/s}</span></a>';
            }
        }

        function renderPrintLabelsColumn(value, meta, model) {
            if (value) {
                let sprite = model.get('plIsLabelPrinted') ? 'sprite-tag-label-black' : 'sprite-tag';
                return '<div data-pl-label="' + model.get('id') + '" class="' + sprite + '" style="width: 16px !important; height: 16px !important; cursor: pointer;"><div/>';
            }
        }
    }
});

//{/block}