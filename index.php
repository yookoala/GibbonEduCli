<?php

use Gibbon\Cli\Launcher\Launcher;

require_once __DIR__ . '/src/Lanucher.php';

// Find the GibbonEdu core package from the parent folders of current working directory.
// Include the autoload.php of it to access the classes.
Launcher::bootstrap(getcwd());
