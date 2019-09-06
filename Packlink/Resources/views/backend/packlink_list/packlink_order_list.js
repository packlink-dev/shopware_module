//{block name="backend/order/view/list/list"}
// {$smarty.block.parent}

Ext.define('Shopware.apps.Packlink.view.Order.List', {
    override: 'Shopware.apps.Order.view.list.List',
    getColumns: function () {
        let result = this.callParent();

        result = this.addPacklinkColumns(result);
        
        return result;
    },
    addPacklinkColumns: function (columns) {
        let i = 0;
        for (i; i < columns.length; i++) {
            if (columns[i].dataIndex === 'dispatchId'){
                break;
            }
        }

        columns.splice(++i, 0, {
            header: 'Packlink Pro',
            dataIndex: 'plReferenceUrl',
            flex:2,
            renderer: renderPacklinkProColumn
        });

        return columns;

        function renderPacklinkProColumn(value) {
            if (value) {
                return '<a href="' +
                    value +
                    '" target="_blank"><img width="16px" src="{link file="backend/_resources/images/logo.png"}" /> </a>';
            }
        }
    }
});

//{/block}