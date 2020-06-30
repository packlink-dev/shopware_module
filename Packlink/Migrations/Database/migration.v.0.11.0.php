<?php

Shopware()->Db()->executeQuery('ALTER TABLE `packlink_entity` ADD `index_8` VARCHAR(255)');

return true;