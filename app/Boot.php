<?php

declare(strict_types=1);

/**
 * This is a patched version of the original file Boot.php
 * from the CodeIgniter4 framework.
 *
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace app;

use CodeIgniter\Cache\FactoriesCache;
use CodeIgniter\CLI\Console;
use CodeIgniter\CodeIgniter;
use CodeIgniter\Config;
use CodeIgniter\Config\DotEnv;
use Config\Autoload;
use Config\Modules;
use Config\Optimize;
use Config\Paths;
use Config\Services;
use function CodeIgniter\is_cli;
use const CodeIgniter\CI_DEBUG;
use const CodeIgniter\EXIT_ERROR;
use const CodeIgniter\EXIT_SUCCESS;

/**
 * Bootstrap for the application
 *
 * @codeCoverageIgnore
 */
class Boot
{
    /**
     * Used by `public/index.php`
     *
     * Context
     *   web:     Invoked by HTTP request
     *   php-cli: Invoked by CLI via `php public/index.php`
     *
     * @return int Exit code.
     */
    public static function bootWeb(Paths $paths): int
    {
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);

        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();

        $configCacheEnabled = class_exists(Optimize::class)
            && (new Optimize())->configCacheEnabled;
        if ($configCacheEnabled) {
            $factoriesCache = static::loadConfigCache();
        }

        static::autoloadHelpers();

        $app = static::initializeCodeIgniter();
        static::runCodeIgniter($app);

        if ($configCacheEnabled) {
            static::saveConfigCache($factoriesCache);
        }

        // Exits the application, setting the exit code for CLI-based
        // applications that might be watching.
        return EXIT_SUCCESS;
    }

    /**
     * Used by `spark`
     *
     * @return int Exit code.
     */
    public static function bootSpark(Paths $paths): int
    {
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);

        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();
        static::autoloadHelpers();

        static::initializeCodeIgniter();
        $console = static::initializeConsole();

        return static::runCommand($console);
    }

    /**
     * Used by `system/Test/bootstrap.php`
     */
    public static function bootTest(Paths $paths): void
    {
        static::loadConstants();
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::loadEnvironmentBootstrap($paths, false);

        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();
        static::autoloadHelpers();
    }

    /**
     * Used by `preload.php`
     */
    public static function preload(Paths $paths): void
    {
        static::definePathConstants($paths);
        static::loadConstants();
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths, false);

        static::loadAutoloader();
    }

    /**
     * Loads the appropriate .env file for the application based on available paths.
     *
     * This method checks for the presence of a `.env` file in various possible
     * locations, using the following precedence:
     *
     * 1. A subdirectory within the custom config directory named after the `HOSTNAME` environment variable.
     * 2. The `.config` subdirectory inside the custom config directory.
     * 3. The root of the custom config directory.
     * 4. The parent of the application directory as a fallback.
     *
     * It then loads the `.env` file using the DotEnv class.
     *
     * @param Paths $paths An object containing various directory paths used by the application.
     *
     * @return void
     */
    protected static function loadDotEnv(Paths $paths): void
    {
        require_once $paths->systemDirectory . '/Config/DotEnv.php';

        // Start with the base config path
        $configPath = $paths->customConfigDirectory;

        // If a HOSTNAME is set in the environment, use it to look for a matching subdirectory
        if (isset($_ENV['HOSTNAME'])) {
            $configPath .= '/' . $_ENV['HOSTNAME'];
        }

        // Determine the correct path to the .env file
        $dotEnv = is_file($configPath . '/.env') ? $configPath :
            (is_file($paths->customConfigDirectory . '/.config/.env') ? $paths->customConfigDirectory . '/.config' :
                (is_file($paths->customConfigDirectory . '/.env') ? $paths->customConfigDirectory : $paths->appDirectory . '/../'));

        // Load the .env file from the determined path
        (new DotEnv($dotEnv))->load();
    }

    protected static function defineEnvironment(): void
    {
        if (! defined('ENVIRONMENT')) {
            // @phpstan-ignore-next-line
            $env = $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT']
                ?? getenv('CI_ENVIRONMENT')
                ?: 'production';

            define('ENVIRONMENT', $env);
        }
    }

    protected static function loadEnvironmentBootstrap(Paths $paths, bool $exit = true): void
    {
        if (is_file($paths->appDirectory . '/Config/Boot/' . ENVIRONMENT . '.php')) {
            require_once $paths->appDirectory . '/Config/Boot/' . ENVIRONMENT . '.php';

            return;
        }

        if ($exit) {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo 'The application environment is not set correctly.';

            exit(EXIT_ERROR);
        }
    }

    /**
     * The path constants provide convenient access to the folders throughout
     * the application. We have to set them up here, so they are available in
     * the config files that are loaded.
     */
    protected static function definePathConstants(Paths $paths): void
    {
        // The path to the application directory.
        if (! defined('APPPATH')) {
            define('APPPATH', realpath(rtrim($paths->appDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        // The path to the project root directory. Just above APPPATH.
        if (! defined('ROOTPATH')) {
            define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
        }

        if (! defined('WEBAPP')) {
            define('WEBAPP', DIRECTORY_SEPARATOR . basename(rtrim(ROOTPATH, DIRECTORY_SEPARATOR)));
        }

        // The path to the system directory.
        if (! defined('SYSTEMPATH')) {
            define('SYSTEMPATH', realpath(rtrim($paths->systemDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        // The path to the writable directory.
        if (! defined('WRITEPATH')) {
            define('WRITEPATH', realpath(rtrim($paths->writableDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        // The path to the tests directory
        if (! defined('TESTPATH')) {
            define('TESTPATH', realpath(rtrim($paths->testsDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        // The path to the upload directory
        if (! defined('UPLOADPATH')) {
            if (!is_dir($paths->uploadsDirectory)) {
                mkdir($paths->uploadsDirectory, 0777, true);
            }
            define('UPLOADPATH', realpath(rtrim($paths->uploadsDirectory, '\\/ ')));
        }

        if (! defined('COMPETITION_UPLOADS')) {
            if (!is_dir($paths->competitionUploadsDirectory)) {
                mkdir($paths->competitionUploadsDirectory, 0777, true);
            }
            define('COMPETITION_UPLOADS', realpath(rtrim($paths->competitionUploadsDirectory, '\\/ ')));
        }

        if (! defined('PUBLICPATH')) {
            if (!is_dir($paths->publicDirectory)) {
                mkdir($paths->publicDirectory, 0777, true);
            }
            define('PUBLICPATH', realpath(rtrim($paths->publicDirectory, '\\/ ')));
        }
    }

    protected static function loadConstants(): void
    {
        $customConfigDirectory = __DIR__ . '/AppConfig';
        $configPath = $customConfigDirectory . (isset($_ENV['HOSTNAME']) ? '/' . $_ENV['HOSTNAME'] : '');

        foreach (['Constants.php', 'AppConstants.php'] as $file) {
            $filePath = $configPath . '/' . $file;
            if (is_file($filePath)) {
                require_once $filePath;
            }
        }

        $defaultConfigDirectory = __DIR__ . '/AppConfig/.config';
        foreach (['Constants.php', 'AppConstants.php'] as $file) {
            $filePath = $defaultConfigDirectory . '/' . $file;
            if (is_file($filePath)) {
                require_once $filePath;
            }
        }

        foreach (['Constants.php', 'AppConstants.php', 'GitVersion.php', 'GitDefaults.php'] as $file) {
            if (is_file(APPPATH . 'Config/' . $file)) {
                require_once APPPATH . 'Config/' . $file;
            }
        }
    }


    protected static function loadCommonFunctions(): void
    {
        // Require app/Common.php file if exists.
        if (is_file(APPPATH . 'Common.php')) {
            require_once APPPATH . 'Common.php';
        }

        // Require system/Common.php
        require_once SYSTEMPATH . 'Common.php';
    }

    /**
     * The autoloader allows all the pieces to work together in the framework.
     * We have to load it here, though, so that the config files can use the
     * path constants.
     */
    protected static function loadAutoloader(): void
    {
        if (! class_exists(Autoload::class, false)) {
            require_once SYSTEMPATH . 'Config/AutoloadConfig.php';
            require_once APPPATH . 'Config/Autoload.php';
            require_once SYSTEMPATH . 'Modules/Modules.php';
            require_once APPPATH . 'Config/Modules.php';
        }

        require_once SYSTEMPATH . 'Autoloader/Autoloader.php';
        require_once SYSTEMPATH . 'Config/BaseService.php';
        require_once SYSTEMPATH . 'Config/Services.php';
        require_once APPPATH . 'Config/Services.php';

        // Initialize and register the loader with the SPL autoloader stack.
        Services::autoloader()->initialize(new Autoload(), new Modules())->register();
    }

    protected static function autoloadHelpers(): void
    {
        Services::autoloader()->loadHelpers();
    }

    protected static function setExceptionHandler(): void
    {
        Services::exceptions()->initialize();
    }

    protected static function checkMissingExtensions(): void
    {
        if (is_file(COMPOSER_PATH)) {
            return;
        }

        // Run this check for manual installations
        $missingExtensions = [];

        foreach ([
                     'intl',
                     'json',
                     'mbstring',
                 ] as $extension) {
            if (! extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }

        if ($missingExtensions === []) {
            return;
        }

        $message = sprintf(
            'The framework needs the following extension(s) installed and loaded: %s.',
            implode(', ', $missingExtensions)
        );

        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo $message;

        exit(EXIT_ERROR);
    }

    protected static function initializeKint(): void
    {
        Services::autoloader()->initializeKint(CI_DEBUG);
    }

    protected static function loadConfigCache(): FactoriesCache
    {
        $factoriesCache = new FactoriesCache();
        $factoriesCache->load('config');

        return $factoriesCache;
    }

    /**
     * The CodeIgniter class contains the core functionality to make
     * the application run, and does all the dirty work to get
     * the pieces all working together.
     */
    protected static function initializeCodeIgniter(): CodeIgniter
    {
        $app = Config\Services::codeigniter();
        $app->initialize();
        $context = is_cli() ? 'php-cli' : 'web';
        $app->setContext($context);

        return $app;
    }

    /**
     * Now that everything is set up, it's time to actually fire
     * up the engines and make this app do its thang.
     */
    protected static function runCodeIgniter(CodeIgniter $app): void
    {
        $app->run();
    }

    protected static function saveConfigCache(FactoriesCache $factoriesCache): void
    {
        $factoriesCache->save('config');
    }

    protected static function initializeConsole(): Console
    {
        $console = new Console();

        // Show basic information before we do anything else.
        // @phpstan-ignore-next-line
        if (is_int($suppress = array_search('--no-header', $_SERVER['argv'], true))) {
            unset($_SERVER['argv'][$suppress]); // @phpstan-ignore-line
            $suppress = true;
        }

        $console->showHeader($suppress);

        return $console;
    }

    protected static function runCommand(Console $console): int
    {
        $exit = $console->run();

        return is_int($exit) ? $exit : EXIT_SUCCESS;
    }
}
