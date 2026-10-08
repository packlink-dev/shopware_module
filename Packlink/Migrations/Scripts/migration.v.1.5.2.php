<?php

use Packlink\Utilities\OrderReference;

// Adds the "Packlink reference" order attribute for shops that installed the plugin before 1.5.2.
// Existing orders get their reference when their draft is created; new orders when they are placed.
OrderReference::createAttribute();

return true;
