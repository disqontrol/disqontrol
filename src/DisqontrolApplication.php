<?php
namespace Disqontrol;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use UnexpectedValueException;
use RuntimeException;

/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
/**
 * Prepare and run whole Disqontrol application.
 *
 * 1) load bootstrap
 * 2) include autoload
 * 3) create Disqontrol instance
 * 4) run symfony console application
 *
 * @author Martin Patera <mzstic@gmail.com>
 * @author Martin Schlemmer
 */
class DisqontrolApplication
{
    const BOOTSTRAP_ARGUMENT = 'bootstrap';
    const DEFAULT_BOOTSTRAP_PATH = 'bootstrap_disqontrol.php';
    const VERBOSITY_DEBUG = 3;

    /**
     * Input arguments of the script
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
     *
     * @throws Exception
     */
    public function __construct(array $argv)
    {
        $this->argv = $argv;
        $this->disqontrol = $this->loadBootstrap();
        $this->includeAutoload();
        if ($this->disqontrol === null) {
            $this->disqontrol = $this->createDisqontrol();
        }
        $this->runApplication();
    }

    /**
     * Try to load bootstrap.
     *
     * If bootstrap option specified load that file, otherwise
     * look for default.
     *
     * @throws \Disqontrol\Exception\FilesystemException
     * @throws UnexpectedValueException
     *
     * @return null|Disqontrol
     */
    private function loadBootstrap() {
        $disqontrol = null;
        $bootstrapArgIndex = $this->findArgumentIndex(self::BOOTSTRAP_ARGUMENT);
        if ($bootstrapArgIndex !== false && ! empty($this->getArgumentValue($bootstrapArgIndex))) {
            $this->bootstrapFile = $this->getArgumentValue($bootstrapArgIndex);

            if (! file_exists($this->bootstrapFile)) {
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

        if (($disqontrol !== null) && (! $disqontrol instanceof Disqontrol)) {
            throw new UnexpectedValueException(
                "Bootstrap must return instance of Disqontrol\\Disqontrol or null."
            );
        }

        return $disqontrol;
    }

    /**
     * Include autoload, location depends on how Disqontrol has been installed.
     */
    private function includeAutoload() {
        if (file_exists(__DIR__.'/../vendor/autoload.php')) {
            require_once __DIR__.'/../vendor/autoload.php';
        } else if (file_exists(__DIR__.'/../../../autoload.php')) {
            require_once __DIR__.'/../../../autoload.php';
        }
    }

    /**
     * Create main Disqontrol class
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
        $disqontrol = new Disqontrol($configFile, $this->isDebug());
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

        $container = $this->disqontrol->getContainer();

        $configuration = $container->get('configuration');
        $configuration->setBootstrapFilePath($this->bootstrapFile);

        $commandIds = $container->getParameter(
            Disqontrol::CONTAINER_COMMANDS_KEY
        );
        foreach ($commandIds as $commandId) {
            $application->add($container->get($commandId));
        }
        $application->run();
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
        $description = 'Path to the bootstrap file. The file must return a Disqontrol instance';
        $defaultValue = null;

        $newOption = new InputOption($name, $shortcut, $mode, $description, $defaultValue);
        $appDefinition->addOption($newOption);
    }

    /**
     * Is the command executed in the debug mode?
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
     * Find index of specified argument in $argv array
     *
     * @param $argumentName
     * @return bool|int
     */
    private function findArgumentIndex($argumentName)
    {
        $index = false;
        $argPrefix = '--'.$argumentName.'=';
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
     * Get value of argument on specified index
     *
     * Arguments are in following format: --argument=value
     *
     * @param int $index
     * @return string
     */
    private function getArgumentValue($index)
    {
        return substr($this->argv[$index], strpos($this->argv[$index], '=') + 1);
    }

    /**
     * Unset argument with index $index
     *
     * We need to remove config and bootstrap options from $_SERVER array,
     * because we don't want symfony application to use them.
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
