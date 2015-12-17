<?php
namespace Disqontrol;

use Disqontrol\Configuration\DisqontrolConfiguration;
use Disqontrol\Configuration\DisqontrolConfigurationDefinition as ConfigDefinition;
use Disqontrol\Exception\ConfigurationException;
use Disqontrol\Exception\FilesystemException;
use Disque\Client;
use Disqontrol\Producer\ProducerInterface;
use Disqontrol\Consumer\ConsumerInterface;
use Disque\Connection\Credentials;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
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
 */
final class Disqontrol
{
    const VERSION = '0.0.1-alpha';
    const NAME = 'Disqontrol';

    const DEFAULT_CONFIG_PATH = 'disqontrol.yml';
    const CONTAINER_CACHE_FILE = 'disqontrol.container.php';
    const SERVICES_FILE = 'services.yml';

    const EVENT_LISTENER_TAG = 'disqontrol.event_listener';
    const EVENT_SUBSCRIBER_TAG = 'disqontrol.event_subscriber';
    const EVENT_DISPATCHER_SERVICE = 'event_dispatcher';

    /**
     * Client for communication with Disque
     *
     * @var Client
     */
    private $disque;

    /**
     * Configuration of Disqontrol
     *
     * @var DisqontrolConfiguration
     */
    private $config;

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
     * If no config file is specified, default config is used instead.
     * @see self::DEFAULT_CONFIG_PATH
     *
     * @param string $configFile
     * @param bool $debug
     */
    public function __construct(
        $configFile = null,
        $debug = false
    ) {
        $this->debug = $debug;
        $this->processConfig($configFile);
        $this->initializeContainer();
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
        if ($this->disque === null) {
            $this->createDisqueClient();
        }
        return $this->disque;
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
        // @TODO implement.
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
        $cache = new ConfigCache($this->config->getCacheDir().'/'.$class.'.php', $this->debug);
        if (!$cache->isFresh()) {
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
        foreach (['cache' => $this->config->getCacheDir(), 'logs' => $this->config->getLogDir()] as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw FilesystemException::cantCreateDirectory($dir, $name);
                }
            } elseif (!is_writable($dir)) {
                throw FilesystemException::cantWriteDirectory($dir, $name);
            }
        }

        $container = $this->getContainerBuilder();
        $container->addObjectResource($this);
        $container->addResource(new FileResource($this->configFile));

        $loader = new YamlFileLoader($container, new FileLocator($this->getConfigDir()));
        $loader->load(self::SERVICES_FILE);

        $compilerPass = new DisqontrolCompilerPass();
        $container->addCompilerPass($compilerPass);

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
            new ParameterBag($this->config->getWholeConfig())
        );
    }

    /**
     * Get the config directory path
     *
     * @return string
     */
    private function getConfigDir()
    {
        return realpath(__DIR__.'/../app/config');
    }

    /**
     * Create Disque client
     *
     * This action is performed only once, when Disque is required for the first time.
     */
    private function createDisqueClient()
    {
        if ($this->disque !== null) {
            return;
        }
        $credentials = $this->getCredentials();
        $this->disque = new Client($credentials);
    }

    /**
     * Create credentials for Disque connection
     *
     * @return Credentials[]
     */
    private function getCredentials()
    {
        $disqueConfig = $this->config->getDisqueConfig();
        $result = [];
        foreach ($disqueConfig as $credentialsConfig) {
            $result[] = new Credentials(
                $credentialsConfig[ConfigDefinition::HOST],
                $credentialsConfig[ConfigDefinition::PORT],
                $credentialsConfig[ConfigDefinition::PASSWORD],
                $credentialsConfig[ConfigDefinition::CONNECTION_TIMEOUT],
                $credentialsConfig[ConfigDefinition::RESPONSE_TIMEOUT]
            );
        }
        return $result;
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
        $processedConfig = $processor->processConfiguration(
            new ConfigDefinition(),
            [$config]
        );
        $this->config = new DisqontrolConfiguration($processedConfig);
    }

    /**
     * Load config file
     *
     * Look for specified file or use default.
     *
     * @param string|null $configFile
     * @return array
     * @throws ConfigurationException
     */
    private function loadConfigFile($configFile)
    {
        if (empty($configFile)) {
            $configFile = self::DEFAULT_CONFIG_PATH;
        }
        if (!file_exists($configFile)) {
            throw ConfigurationException::configFileNotFound($configFile);
        }
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
        $hash = substr(md5($this->configFile), 0, 8);
        return 'Disqontrol'.($this->debug ? 'Debug' : '').'Container_' . $hash;
    }
}