<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol;

use Disqontrol\Console\Command\AddJobCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use UnexpectedValueException;
use RuntimeException;

/**
 * Prepare and run the Disqontrol command-line application
 *
 * 1) Load the bootstrap file
 * 2) Include autoload
 * 3) Create a Disqontrol instance
 * 4) Run the Symfony console application
 *
 * @author Martin Patera <mzstic@gmail.com>
 * @author Martin Schlemmer
 */
class DisqontrolApplication
{
    /**
     * A relative path to the Disqontrol executable
     * The space at the end is there on purpose for easier command composition.
     */
    const COMMAND_LINE_EXECUTABLE = '/bin/disqontrol ';

    const BOOTSTRAP_ARGUMENT = 'bootstrap';
    const DEFAULT_BOOTSTRAP_PATH = 'disqontrol_bootstrap.php';
    const VERBOSITY_DEBUG = 3;

    /**
     * Input arguments of the script
     *
     * @var array
     */
    private $argv;

    /**
     * @var Disqontrol
     */
    private $disqontrol;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string A path to the bootstrap file
     */
    private $bootstrapFile = '';

    /**
     * Run the whole application
     *
     * @param array $argv
     */
    public function __construct(array $argv)
    {
        $this->argv = $argv;
        try {
            $this->includeAutoload();
            $this->disqontrol = $this->loadBootstrap();
            if ($this->disqontrol === null) {
                $this->disqontrol = $this->createDisqontrol();
            }
            $this->runApplication();
        } catch (Exception $e) {
            file_put_contents('php://stderr', $e->getMessage() . "\n");
            die(1);
        }
    }

    /**
     * Get the Disqontrol root directory
     *
     * @return string
     */
    public static function getRootDir()
    {
        return realpath(__DIR__ . '/..');
    }

    /**
     * Get the Disqontrol command line executable
     *
     * @return string
     */
    public static function getExecutable()
    {
        return self::getRootDir() . self::COMMAND_LINE_EXECUTABLE;
    }

    /**
     * Try to load the bootstrap file
     *
     * If a bootstrap option is specified, load that file, otherwise
     * look for the default file.
     *
     * @see self::DEFAULT_BOOTSTRAP_PATH
     *
     * @throws \Disqontrol\Exception\FilesystemException
     * @throws UnexpectedValueException
     *
     * @return null|Disqontrol
     */
    private function loadBootstrap()
    {
        $disqontrol = null;
        $bootstrapArgIndex = $this->findArgumentIndex(self::BOOTSTRAP_ARGUMENT);
        if ($bootstrapArgIndex !== false && ! empty($this->getArgumentValue($bootstrapArgIndex))) {
            $this->bootstrapFile = $this->getArgumentValue($bootstrapArgIndex);

            if ( ! file_exists($this->bootstrapFile)) {
                throw new RuntimeException(
                    'The bootstrap file "' . $this->bootstrapFile . '" has not been found'
                );
            }
            $disqontrol = require_once $this->bootstrapFile;
            $this->removeArgument($bootstrapArgIndex);

        } else if (file_exists(self::DEFAULT_BOOTSTRAP_PATH)) {
            $this->bootstrapFile = self::DEFAULT_BOOTSTRAP_PATH;
            $disqontrol = require_once self::DEFAULT_BOOTSTRAP_PATH;
        }

        if (($disqontrol !== null) && ( ! $disqontrol instanceof Disqontrol)) {
            throw new UnexpectedValueException(
                sprintf(
                    'The bootstrap file "%s" must return an instance of Disqontrol\Disqontrol or null.',
                    $this->bootstrapFile
                )
            );
        }

        return $disqontrol;
    }

    /**
     * Include the autoload file
     *
     * Its location depends on how Disqontrol has been installed.
     */
    private function includeAutoload()
    {
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        } else if (file_exists(__DIR__ . '/../../../autoload.php')) {
            require_once __DIR__ . '/../../../autoload.php';
        }
    }

    /**
     * Create the main Disqontrol object
     *
     * @throws Exception
     *
     * @return \Disqontrol\Disqontrol
     */
    private function createDisqontrol()
    {
        $configFile = null;
        $configFileArgIndex = $this->findArgumentIndex('config');
        if ($configFileArgIndex !== false) {
            $configFile = $this->getArgumentValue($configFileArgIndex);
            $this->removeArgument($configFileArgIndex);
        }

        $workerFactoryCollection = null;
        $disqontrol = new Disqontrol(
            $configFile,
            $workerFactoryCollection,
            $this->isDebug()
        );

        return $disqontrol;
    }

    /**
     * Prepare and run the application itself
     *
     * @throws Exception
     */
    private function runApplication()
    {
        $application = new Application(
            Disqontrol::NAME,
            Disqontrol::VERSION
        );

        $this->addGlobalBootstrapArgument($application);
        $this->registerBootstrapFilePath();
        $this->registerCommands($application);

        $input = new ArgvInput($this->argv);
        if ( ! $this->userAsksForHelp($input)) {
            $this->disqontrol->checkPhpWorkers();
        }

        $application->run($input);
    }

    /**
     * Add a console argument --bootstrap to all commands by default
     *
     * This doesn't have an effect inside the commands because it is processed
     * here, before the application is run. Its purpose is to add the argument
     * to the command help so that users understand that it exists.
     *
     * @param Application $application
     */
    private function addGlobalBootstrapArgument(Application $application)
    {
        $appDefinition = $application->getDefinition();
        $name = self::BOOTSTRAP_ARGUMENT;
        $shortcut = null;
        $mode = InputOption::VALUE_OPTIONAL;
        $description = 'Path to the bootstrap file. The file can return a Disqontrol instance';
        $defaultValue = null;

        $newOption = new InputOption($name, $shortcut, $mode, $description, $defaultValue);
        $appDefinition->addOption($newOption);
    }

    /**
     * Register the current bootstrap path as a configuration parameter
     */
    public function registerBootstrapFilePath()
    {
        $container = $this->disqontrol->getContainer();

        $configuration = $container->get('configuration');
        $configuration->setBootstrapFilePath($this->bootstrapFile);
    }

    /**
     * Register available commands in the Symfony application
     *
     * @param Application $application
     */
    private function registerCommands(Application $application)
    {
        $container = $this->disqontrol->getContainer();

        $commandIds = $container->getParameter(
            Disqontrol::CONTAINER_COMMANDS_KEY
        );

        foreach ($commandIds as $commandId) {
            $command = $container->get($commandId);
            $application->add($command);
        }
    }

    /**
     * Check whether the user is asking for help with CLI commands
     *
     * @param InputInterface $input
     *
     * @return bool
     */
    private function userAsksForHelp(InputInterface $input)
    {
        // null, or no command, falls back to "list"
        $skippedCommands = [null, 'help', 'list'];
        $command = $input->getFirstArgument();

        $skippedParameters = ['--help', '-h'];
        $onlyParams = true;

        return (in_array($command, $skippedCommands)
            || $input->hasParameterOption($skippedParameters, $onlyParams));
    }

    /**
     * Is the command being executed in a debug mode?
     *
     * Check for --verbose=3 option set or -vvv short option.
     *
     * @return bool
     */
    private function isDebug()
    {
        if ($this->debug !== null) {
            return $this->debug;
        }
        $verbosityArgIndex = $this->findArgumentIndex('verbose');
        if ($verbosityArgIndex !== false) {
            $verbosityLevel = $this->getArgumentValue($verbosityArgIndex);
            $this->debug = $verbosityLevel == self::VERBOSITY_DEBUG;
        } else {
            $shortOptionIndex = array_search('-vvv', $this->argv);
            $this->debug = $shortOptionIndex !== false;
        }

        return $this->debug;
    }

    /**
     * Find the index of the specified argument in the $argv array
     *
     * @param $argumentName
     *
     * @return bool|int
     */
    private function findArgumentIndex($argumentName)
    {
        $index = false;
        $argPrefix = '--' . $argumentName . '=';
        $argPrefixLength = strlen($argPrefix);
        foreach ($this->argv as $key => $value) {
            if (substr($value, 0, $argPrefixLength) == $argPrefix) {
                $index = $key;
                break;
            }
        }

        return $index;
    }

    /**
     * Get the value of an argument at the specified index
     *
     * Arguments are in the following format: --argument=value
     *
     * @param int $index
     *
     * @return string
     */
    private function getArgumentValue($index)
    {
        return substr($this->argv[$index], strpos($this->argv[$index], '=') + 1);
    }

    /**
     * Unset the argument with the given index
     *
     * @param int $index
     */
    private function removeArgument($index)
    {
        if ($index !== false) {
            unset($this->argv[$index]);
            unset($_SERVER['argv'][$index]);
        }
    }
}
