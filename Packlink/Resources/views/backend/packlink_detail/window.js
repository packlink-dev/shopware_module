//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.Packlink.packlink_detail.Window', {
    override: 'Shopware.apps.Order.view.detail.Window',

    createTabPanel: function () {
        let me = this,
            result = me.callParent();

        let tab = Ext.create('Shopware.apps.Packlink.packlink_detail.PacklinkOrderDetailTab');
        tab.record = me.record;
        result.add(tab);

        return result;
    },
});
//{/block}