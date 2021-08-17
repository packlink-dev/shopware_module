<?php

Shopware()->Db()->executeQuery("ALTER TABLE packlink_entity RENAME TO s_plugin_packlink_entity");

return true;
