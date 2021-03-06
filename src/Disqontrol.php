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

use Disqontrol\DisqontrolApplication as App;
use Disqontrol\Configuration\ConfigDefinition;
use Disqontrol\Configuration\ConsoleCommandsCompilerPass;
use Disqontrol\Configuration\FailureStrategiesCompilerPass;
use Disqontrol\Exception\ConfigurationException;
use Disqontrol\Exception\FilesystemException;
use Disqontrol\Logger\LineFormatter;
use Disqontrol\Logger\MessageFormatter as msg;
use Disqontrol\Worker\WorkerFactoryCollection;
use Disqontrol\Worker\WorkerFactoryCollectionInterface;
use Disque\Client;
use Disqontrol\Producer\ProducerInterface;
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
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\Yaml\Yaml;

/**
 * The main Disqontrol class
 *
 * This is the public interface between Disqontrol and other code.
 * You can use events to hook into other classes, for more information
 * see the documentation.
 *
 * This place also maintains the DI container and handles its creation,
 * caching, building etc.
 *
 * @author Martin Patera <mzstic@gmail.com>
 * @author Martin Schlemmer
 */
class Disqontrol
{
    /**
     * The application name and version
     */
    const NAME = 'Disqontrol';
    const VERSION = '0.3.3';
    
    /**
     * Default paths
     */
    const DEFAULT_CONFIG_PATH = 'disqontrol.yml';
    const CONTAINER_CACHE_FILE = 'disqontrol.container.php';
    const SERVICES_FILE = 'services.yml';
    const APP_CONFIG_DIR_PATH = '/config';
    
    /**
     * Keys for the service container parameters
     */
    const CONTAINER_COMMANDS_KEY = 'disqontrol.commands';
    
    /**
     * Service ID and tags for the service container
     */
    const EVENT_LISTENER_TAG = 'disqontrol.event_listener';
    const EVENT_SUBSCRIBER_TAG = 'disqontrol.event_subscriber';
    const EVENT_DISPATCHER_SERVICE = 'event_dispatcher';
    
    /**
     * A collection of worker factories that can create PHP workers
     *
     * @var WorkerFactoryCollectionInterface
     */
    private $workerFactoryCollection;
    
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
     * @var string Configuration file directory
     */
    private $configFileDir;
    
    /**
     * @var string Configuration file path
     */
    private $configFile;
    
    /**
     * @var array Array of configuration parameters
     */
    private $configParams;
    
    /**
     * If no config file path is specified, the default config path is used instead
     *
     * @see self::DEFAULT_CONFIG_PATH
     *
     * @param string|null                           $configFilePath
     * @param WorkerFactoryCollectionInterface|null $workerFactoryCollection
     * @param bool                                  $debug
     * @param string|null                           $bootstrapFilePath
     *
     * @throws ConfigurationException
     */
    public function __construct(
        $configFilePath = null,
        WorkerFactoryCollectionInterface $workerFactoryCollection = null,
        $debug = false,
        $bootstrapFilePath = null
    ) {
        $this->debug = (bool)$debug;
        $this->processConfig($configFilePath);
        $this->initializeContainer();
        
        $config = $this->container->get('configuration');
        $config->setBootstrapFilePath($bootstrapFilePath);
        
        $this->prepareLogger();
        
        if ($workerFactoryCollection === null) {
            $workerFactoryCollection = new WorkerFactoryCollection();
        }
        $this->workerFactoryCollection = $workerFactoryCollection;
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
     * Get a job producer
     *
     * Producer can send jobs to the queue.
     * If you set $synchronousMode to true, the method returns a synchronous
     * producer, that processes the job immediately, skipping Disque.
     *
     * @param bool $synchronousMode
     *
     * @return ProducerInterface
     */
    public function getProducer($synchronousMode = false)
    {
        if ($synchronousMode) {
            return $this->container->get('synchronous_producer');
        }
        
        return $this->container->get('producer');
    }
    
    /**
     * @return WorkerFactoryCollectionInterface|null
     */
    public function getWorkerFactoryCollection()
    {
        return $this->workerFactoryCollection;
    }
    
    /**
     * Check whether we have all PHP workers
     *
     * @throws ConfigurationException
     */
    public function checkPhpWorkers()
    {
        $config = $this->container->get('configuration');
        $phpWorkers = $config->getPhpWorkers();
        
        foreach ($phpWorkers as $workerName) {
            if ( ! $this->workerFactoryCollection->hasWorkerFactory($workerName)) {
                // We're missing a PHP worker we might need. Warn the user
                // in the STD_ERR but don't quit, otherwise for example
                // the help command won't work.
                
                $warning = msg::phpJobWorkerFromConfigurationNotFound($workerName);
                // We presume the user is sitting at the terminal right now
                file_put_contents('php://stderr', $warning . PHP_EOL);
                // But we also don't want him to miss the message in case he
                // doesn't see it.
                $logger = $this->container->get('monolog_logger');
                $logger->warn($warning);
            }
        }
    }
    
    /**
     * Initialize the service container
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    private function initializeContainer()
    {
        $containerClass = $this->getContainerClass();
        
        $alwaysCheckCache = true;
        $cache = new ConfigCache($this->getCacheDir() . '/' . $containerClass . '.php', $alwaysCheckCache);
        if ( ! $cache->isFresh()) {
            $container = $this->buildContainer();
            $container->compile();
            $this->dumpContainer($cache, $container, $containerClass);
        }
        require_once $cache->getPath();
        $this->container = new $containerClass();
        $this->container->set('disqontrol', $this);
        $configFactory = $this->container->get('configuration_factory');
        $configFactory->setConfigArray($this->configParams);
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
        
        $container = new ContainerBuilder();
        $container->addObjectResource($this);
        $container->addResource(new FileResource($this->configFile));
        
        $loader = new YamlFileLoader($container, new FileLocator($this->getConfigDir()));
        $loader->load(self::SERVICES_FILE);
        $container->addResource(
            new FileResource($this->getConfigDir() . '/' . self::SERVICES_FILE)
        );
        
        $container->addCompilerPass(new ConsoleCommandsCompilerPass());
        $container->addCompilerPass(new FailureStrategiesCompilerPass());
        
        $container->addCompilerPass(
            new RegisterListenersPass(
                self::EVENT_DISPATCHER_SERVICE,
                self::EVENT_LISTENER_TAG,
                self::EVENT_SUBSCRIBER_TAG
            )
        );
        
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
        $this->configFileDir = realpath(dirname($configFile));
        
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
        $streamHandler = new StreamHandler($this->getLogDir() . '/disqontrol.log', Logger::DEBUG);
        $lineFormatter = new LineFormatter();
        $streamHandler->setFormatter($lineFormatter);
        
        $logger = $this->container->get('monolog_logger');
        $logger->pushHandler($streamHandler);
    }
    
    /**
     * Get the config directory path
     *
     * @return string
     */
    private function getConfigDir()
    {
        return App::getRootDir() . self::APP_CONFIG_DIR_PATH;
    }
    
    /**
     * Get the cache directory path from the configuration parameters
     *
     * We need this parameter before the configuration object is created,
     * otherwise we would ask it.
     *
     * If the log path is relative, it is used as relative to the configuration
     * file.
     *
     * @return string
     */
    private function getCacheDir()
    {
        $cacheDir = $this->configParams[ConfigDefinition::CACHE_DIR];
        
        return $this->resolveDirFromConfig($cacheDir);
    }
    
    /**
     * Get the log directory path from the configuration parameters
     *
     * We need this parameter before the configuration object is created,
     * otherwise we would ask it.
     *
     * If the log path is relative, it is used as relative to the configuration
     * file.
     *
     * @return string
     */
    private function getLogDir()
    {
        $logDir = $this->configParams[ConfigDefinition::LOG_DIR];
        
        return $this->resolveDirFromConfig($logDir);
    }
    
    /**
     * Resolve a path from the configuration that can be relative to the
     * configuration file, or absolute
     *
     * @param string $dir
     *
     * @return string Resolved directory from the configuration
     */
    private function resolveDirFromConfig($dir)
    {
        if ($this->isPathRelative($dir)) {
            $dir = $this->configFileDir . '/' . $dir;
        }
        
        return $dir;
    }
    
    /**
     * Check if the given path is relative
     *
     * @param string $path
     *
     * @return bool
     */
    private function isPathRelative($path)
    {
        return substr($path, 0, 1) !== '/';
    }
}
