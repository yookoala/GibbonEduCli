<?php

namespace Gibbon\Cli\Launcher;

/**
 * Utility class for functions in the CLI launcher.
 */
class Launcher
{
    /**
     * Bootstrap the CLI environment.
     *
     * Workflow:
     * 1. Searching for the gibbonedu/core project root folder.
     * 2. Change working directory to the gibbonedu/core root folder.
     * 3. Load the composer autoload file there.
     *
     * Error in the process will result in exit with code 1.
     * Error messages will be sent to STDERR.
     *
     * @param string $pwd  The working directory path.
     *
     * @return void
     */
    public static function bootstrap(string $pwd)
    {
        list($options, $args) = static::getOptions('h', ['path:', 'help']);
        $pwd = $options['path'] ?? $pwd;
        try {
            static::doBootstrap($pwd);
        } catch (\Exception $e) {
            if (isset($options['help']) || isset($options['h'])) {
                // Simply print the help message of the lanucher.
                echo static::helpMessage();
                exit(0);
            }
            fwrite(STDERR, "Bootstrap Error: {$e->getMessage()}\n");
            fwrite(STDERR, "Abort.\n");
            exit(1);
        }

        if (isset($options['help']) || isset($options['h'])) {
            // Simply print the help message of the lanucher.
            echo static::helpMessage();
            exit(0);
        }
    }

    /**
     * Does the actual work to bootstrap.
     *
     * @param string $pwd
     * @return void
     *
     * @throws \Exception  Any problem encountered in bootstrap process.
     */
    private static function doBootstrap(string $pwd)
    {
        try {
            echo "===================\n";
            echo "Gibbon CLI Launcher\n";
            echo "-------------------\n";
            if (!is_dir(getcwd() . DIRECTORY_SEPARATOR . $pwd)) {
                echo 'Provided Path: ' . $pwd . "\n";
                throw new \Exception('The path provided is not a directory: ' . $pwd);
            }
            $pwd = realpath($pwd);
            echo 'Working Directory: ' . $pwd . "\n";
            $gibbonRoot = static::findRoot(static::pathParents($pwd), 'gibbonedu/core');
            echo 'Gibbon Directory:  ' . $gibbonRoot . "\n";
            $autoloadFile = $gibbonRoot . '/vendor/autoload.php';
            echo 'Autoload File:     ' . $autoloadFile . "\n";
            echo "===================\n\n";
        } catch (\Exception $e) {
            // print the ending declorations and rethrow error.
            echo "===================\n\n";
            throw $e;
        }

        if (!is_file($autoloadFile) || !is_readable($autoloadFile)) {
            throw new \Exception('Unable to find or open autoload file: ' . $autoloadFile);
        }
        chdir($gibbonRoot); // Change to the working directory for more predictable behaviour.
        require_once $autoloadFile;
    }

    /**
     * Parse options from global argv.
     *
     * @param $shortopts  A string of short options. See getopt() for more details.
     * @param $longopts   An array of long options. See getopt() for more details.
     *
     * @return array  An array of 2 items:
     *
     * 1. options: An assoc array of options.
     * 2. args:    An array of remaining arguments.
     */
    private static function getOptions(string $short_options, array $long_options): array
    {
        global $argv;
        $optind = 0;
        $options = \getopt($short_options, $long_options);
        $args = array_slice($argv, $optind);
        return [$options, $args];
    }

    /**
     * From a directory path, generates a list of paths including itself
     * and its parent paths. The order would be from inner to outer. All
     * trailing slashes are removed.
     *
     * @param string  $path         A directory path.
     * @param bool    $includeSelf  Should the original path be included.
     *                              Default: true
     *
     * @return iterable  An iterable of paths from inner to outer (longest
     *                   to shortest).
     */
    public static function pathParents(string $path, bool $includeSelf = true): iterable {
        if (!is_dir($path)) {
            throw new \Exception("Invalid directory: {$path}");
        }
        $path = realpath($path); // convert to realpath.
        $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        $end = $includeSelf ? 0 : 1;
        for ($l=sizeof($parts); $l>=$end; $l--) {
            yield DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
            array_pop($parts);
        }
    }

    /**
     * Find the root folder of a composer project.
     *
     * Will look for composer.json file in directories provided to find
     * the first one with composer.json of the specified package name.
     *
     * @param iterable  $directories  An iterable of directories.
     * @param string    $packageName  The package name to search for.
     *
     * @return string  The first directory (in directories) that contains
     *                 the composer.json with package name matches the
     *                 search target.
     *
     * @throws \Exception  If unable to find the given package.
     */
    public static function findRoot(iterable $directories, string $packageName): string
    {
        foreach ($directories as $directory) {
            $composerConfFilename = $directory . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($composerConfFilename)) {
                $composerConf = json_decode(file_get_contents($composerConfFilename));
                if ($composerConf->name === $packageName) {
                    return $directory;
                }
            }
        }
        throw new \Exception('Unable to find ' . $packageName . ' package from parents of the working directory.');
    }

    public static function helpMessage()
    {
        $scriptname = $_SERVER['SCRIPT_NAME'] ?? __FILE__;
        $filename = basename($scriptname);
        return <<<TEXT
Gibbon CLI Launcher is a command launcher to access Gibbon CLI features.

This should be run against a Gibbon installation directory.
Without a Gibbon installation, this launcher can do very little.

Usage: {$filename} [--help] [--path=PATH_TO_GIBBON]

Options:
  --help  Print this message.
  --path  Path to a Gibbon installation folder.
          Default: current working directory.
TEXT;
    }
}
