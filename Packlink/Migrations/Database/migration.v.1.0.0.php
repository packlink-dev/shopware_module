<?php

Shopware()->Db()->executeQuery(
    "UPDATE packlink_entity
     SET packlink_entity.data = replace(packlink_entity.data, 'Logeecom', 'Packlink') 
     WHERE packlink_entity.data like '%Logeecom%'"
);

return true;
