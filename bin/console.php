<?php

use Henrik\Framework\App;
use Henrik\Framework\ConsoleKernel;

require 'vendor/autoload.php';

(new App(new ConsoleKernel()))->run();