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
            flex:2,
            renderer: renderPacklinkProColumn
        });

        columns.splice(columns.length - 1, 0, {
            header: '{s name="order/print/labels"}Labels{/s}',
            dataIndex: 'plHasLabel',
            flex:1,
            renderer: renderPrintLabelsColumn
        });

        return columns;

        function renderPacklinkProColumn(value) {
            if (value) {
                return '<a href="' +
                    value +
                    '" target="_blank"><img width="16px" src="{link file="backend/_resources/images/logo.png"}" /> </a>';
            }
        }

        function renderPrintLabelsColumn(value, meta, model) {
            if (value) {
                let sprite = model.get('plIsLabelPrinted') ? 'sprite-tag-label-black': 'sprite-tag';
                return '<div data-pl-label="' + model.get('id') + '" class="' + sprite + '" style="width: 16px !important; height: 16px !important; cursor: pointer;"><div/>';
            }
        }
    }
});

//{/block}