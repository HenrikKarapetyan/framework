<?php

use Henrik\Framework\App;

require 'vendor/autoload.php';

$services = require 'config/services.php';

(new App())->run();