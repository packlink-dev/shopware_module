//{namespace name=backend/packlink/configuration}

// Packlink order details tab

Ext.define('Shopware.apps.Packlink.packlink_detail.PacklinkOrderDetailTab', {
    extend: 'Ext.panel.Panel',
    title: '{s name="shipment/details/title"}Packlink Shipping{/s}',
    alias: 'widget.packlink_order_details',
    layout: 'column',
    cls: 'shopware-form'
});