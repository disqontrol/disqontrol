<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol;

use Disqontrol\Configuration\ConfigDefinition;
use Disqontrol\Configuration\ConsoleCommandsCompilerPass;
use Disqontrol\Exception\ConfigurationException;
use Disqontrol\Exception\FilesystemException;
use Disque\Client;
use Disqontrol\Producer\ProducerInterface;
use Disqontrol\Consumer\ConsumerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\Yaml\Yaml;

/**
 * Main Disqontrol class
 *
 * This is public interface between Disqontrol and other code.
 * You can use events to hook into other classes, for more information
 * see documentation.
 *
 * This place also maintains DI container and handles its creation,
 * caching, building etc.
 *
 * @author Martin Patera <mzstic@gmail.com>
 * @author Martin Schlemmer
 */
final class Disqontrol
{
    /**
     * The application name and version
     */
    const NAME = 'Disqontrol';
    const VERSION = '0.0.1-alpha';

    /**
     * Default paths
     */
    const DEFAULT_CONFIG_PATH = 'disqontrol.yml';
    const CONTAINER_CACHE_FILE = 'disqontrol.container.php';
    const SERVICES_FILE = 'services.yml';
    const APP_CONFIG_DIR_PATH = '/../app/config';

    /**
     * Keys for the service container parameters
     */
    const CONTAINER_CONFIG_KEY = 'configuration';
    const CONTAINER_COMMANDS_KEY = 'disqontrol.commands';

    /**
     * Service ID and tags for the service container
     */
    const EVENT_LISTENER_TAG = 'disqontrol.event_listener';
    const EVENT_SUBSCRIBER_TAG = 'disqontrol.event_subscriber';
    const EVENT_DISPATCHER_SERVICE = 'event_dispatcher';

    /**
     * Is Disqontrol running in debug mode?
     *
     * @var bool
     */
    private $debug = false;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var string Configuration file
     */
    private $configFile;

    /**
     * @var array Array of configuration parameters
     */
    private $configParams;

    /**
     * If no config file is specified, default config is used instead.
     *
     * @see self::DEFAULT_CONFIG_PATH
     *
     * @param string $configFile
     * @param bool   $debug
     */
    public function __construct(
        $configFile = null,
        $debug = false
    )
    {
        $this->debug = $debug;
        $this->processConfig($configFile);
        $this->initializeContainer();

        $this->prepareLogger();
    }

    /**
     * Get the Disqontrol service container
     *
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the Disque client
     *
     * @return Client
     */
    public function getDisque()
    {
        return $this->container->get('disque');
    }

    /**
     * Get a queue producer
     *
     * Producer can send jobs to the queue.
     *
     * @param bool $synchronousMode Is synchronous mode on?
     *
     * @return ProducerInterface
     */
    public function getProducer($synchronousMode = false)
    {
        // TODO Implement a synchronous producer
        return $this->container->get('producer');
    }

    /**
     * Get a queue consumer
     *
     * Consumer can listen to one or more queues.
     *
     * @return ConsumerInterface
     */
    public function getConsumer()
    {
        // @TODO implement.
    }

    /**
     * Initialize the service container
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    private function initializeContainer()
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir() . '/' . $class . '.php', $this->debug);
        if ( ! $cache->isFresh()) {
            $container = $this->buildContainer();
            $container->compile();
            $this->dumpContainer($cache, $container, $class);
        }
        require_once $cache->getPath();
        $this->container = new $class();
        $this->container->set('disqontrol', $this);
    }

    /**
     * Build the service container
     *
     * @return ContainerBuilder The compiled service container
     *
     * @throws FilesystemException
     */
    private function buildContainer()
    {
        foreach (['cache' => $this->getCacheDir(), 'logs' => $this->getLogDir()] as $name => $dir) {
            if ( ! is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                    throw FilesystemException::cantCreateDirectory($dir, $name);
                }
            } elseif ( ! is_writable($dir)) {
                throw FilesystemException::cantWriteDirectory($dir, $name);
            }
        }

        $container = $this->getContainerBuilder();
        $container->addObjectResource($this);
        $container->addResource(new FileResource($this->configFile));

        $loader = new YamlFileLoader($container, new FileLocator($this->getConfigDir()));
        $loader->load(self::SERVICES_FILE);

        $container->addCompilerPass(new ConsoleCommandsCompilerPass());

        $container->addCompilerPass(new RegisterListenersPass(
            self::EVENT_DISPATCHER_SERVICE,
            self::EVENT_LISTENER_TAG,
            self::EVENT_SUBSCRIBER_TAG
        ));

        return $container;
    }

    /**
     * Dump the service container to PHP code in the cache
     *
     * @param ConfigCache      $cache     The config cache
     * @param ContainerBuilder $container The service container
     * @param string           $class     The name of the class to generate
     */
    private function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class)
    {
        $dumper = new PhpDumper($container);
        $content = $dumper->dump(array('class' => $class, 'file' => $cache->getPath()));
        $cache->write($content, $container->getResources());
    }

    /**
     * Get the container builder
     *
     * @return ContainerBuilder
     */
    private function getContainerBuilder()
    {
        return new ContainerBuilder(
            new ParameterBag([self::CONTAINER_CONFIG_KEY => $this->configParams])
        );
    }

    /**
     * Load and process configuration file.
     *
     * @param string $configFile
     */
    private function processConfig($configFile = null)
    {
        $config = $this->loadConfigFile($configFile);
        $processor = new Processor();
        $processedParams = $processor->processConfiguration(
            new ConfigDefinition(),
            [$config]
        );

        $this->configParams = $processedParams['disqontrol'];
    }

    /**
     * Load config file
     *
     * Look for specified file or use default.
     *
     * @param string|null $configFile
     *
     * @return array
     * @throws ConfigurationException
     */
    private function loadConfigFile($configFile)
    {
        if (empty($configFile)) {
            $configFile = self::DEFAULT_CONFIG_PATH;
        }
        if ( ! file_exists($configFile)) {
            throw ConfigurationException::configFileNotFound($configFile);
        }

        $this->configFile = $configFile;

        return Yaml::parse(file_get_contents($configFile));
    }

    /**
     * Create container class name
     *
     * We need to cache different container classes for different
     * config files. If the cache is fresh is determined by ConfigCache.
     *
     * @return string
     */
    private function getContainerClass()
    {
        $hashInput = $this->configFile . self::VERSION;
        $hash = substr(md5($hashInput), 0, 8);

        return 'Disqontrol' . ($this->debug ? 'Debug' : '') . 'Container_' . $hash;
    }

    /**
     * Prepare monolog logger
     */
    private function prepareLogger()
    {
        $logger = $this->container->get('logger');
        $streamHandler = new StreamHandler($this->getLogDir() . '/disqontrol.log', Logger::DEBUG);
        $logger->pushHandler($streamHandler);
    }

    /**
     * Get the config directory path
     *
     * @return string
     */
    private function getConfigDir()
    {
        return realpath(__DIR__ . self::APP_CONFIG_DIR_PATH);
    }

    /**
     * Get the cache directory path from the configuration parameters
     *
     * We need this parameter before the configuration object is created,
     * otherwise we would ask it.
     *
     * @return string
     */
    private function getCacheDir()
    {
        return $this->configParams[ConfigDefinition::CACHE_DIR];
    }

    /**
     * Get the log directory ppath from the configuration parameters
     *
     * We need this parameter before the configuration object is created,
     * otherwise we would ask it.
     *
     * @return string
     */
    private function getLogDir()
    {
        return $this->configParams[ConfigDefinition::LOG_DIR];
    }

}
