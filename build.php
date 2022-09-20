<?php

$shortopts  = '';
$shortopts .= 't:';
$shortopts .= 'h';
$longopts  = [
    'target:',
    'help',
];
$optind = 0;
$options = getopt($shortopts, $longopts, $optind);
$target = $options['target'] ?? $options['t'] ?? 'gibbon-cli.phar';

if (isset($options['help'])) {
    $filename = basename(__FILE__);
    echo <<<TEXT
Usage:
php {$filename} [--target=OUTPUT_FILEPATH] [--help]

Options:
  --help    Show this message.
  --target  Define the full path of the build target.
            Default: gibbon.phar
TEXT;
    exit(0);
}

// Make sure the working directory is in the same folder of this script.
chdir(__DIR__);

// If the target directory does not exists, create recursively.
$targetDir = dirname($target);
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
if (!is_writable($targetDir)) {
    throw new \Exception("{$targetDir} is not writable.");
}

// If the target is not of .phar extension, need to rename later.
$renameTarget = false;
if (strtolower(pathinfo($target, PATHINFO_EXTENSION)) !== 'phar') {
    $renameTarget = $target;
    $target .= '.phar';
}

// Build a phar file with shebang
$phar = new \Phar($target, 0, basename($target));
$phar->startBuffering();
$phar->addFile('index.php');
$phar->buildFromIterator(
    new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
        __DIR__ . '/src',
        \FilesystemIterator::SKIP_DOTS
    )),
    __DIR__,
);
$stub = "#!/usr/bin/env php\n" . $phar->createDefaultStub('./index.php', './index.php');
$phar->setStub($stub);
$phar->stopBuffering();

if (!is_file($phar->getPath())) {
    throw new \Exception("{$phar->getPath()} not found.");
}

// Make the result file executable
chmod($phar->getPath(), 0755);

// Rename if needed.
if ($renameTarget !== false) rename($target, $renameTarget);
