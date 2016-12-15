<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
 * This is an example bootstrap file for Disqontrol
 *
 * It serves to bridge your application and Disqontrol for cases where Disqontrol
 * runs as a command line application.
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}

require_once 'ExampleWorkerFactory.php';
require_once 'ExampleWorker.php';

// If you want to use PHP workers, follow these 4 steps
// 1. Instantiate the WorkerFactoryCollection
$workerFactoryCollection = new Disqontrol\Worker\WorkerFactoryCollection();

// 2. Write and register a code that sets up the environment for your workers
$workerEnvironmentSetup = function() {
    // Whatever you return here, will be injected into your worker factories
    // This code will only be executed if needed (if Disqontrol must run any
    // of your PHP workers)
    
    // $serviceContainer = require_once 'application_bootstrap.php';
    $serviceContainer = 'Example Framework Service Container';
    return $serviceContainer;
};
$workerFactoryCollection->registerWorkerEnvironmentSetup($workerEnvironmentSetup);

// 3. Instantiate and register factories for all your PHP workers
$picResizeWorkerFactory = new ExampleWorkerFactory();
$workerFactoryCollection->addWorkerFactory('PicResizeWorker', $picResizeWorkerFactory);
// The name, 'PicResizeWorker', is equal to what you call your worker
// in the configuration

// Repeat for all workers
$rssUpdateWorkerFactory = new ExampleWorkerFactory();
$workerFactoryCollection->addWorkerFactory('RssUpdateWorker', $rssUpdateWorkerFactory);

// 4. Now instantiate Disqontrol and put it all together
$configFile = __DIR__ . '/../disqontrol.yml.dist';
$debug = true;

$disqontrol = new Disqontrol\Disqontrol($configFile, $workerFactoryCollection, $debug);

// Don't forget to return the Disqontrol instance on the last line
return $disqontrol;

/**
 * ===
 * For the other direction, when you need to connect to Disqontrol from your
 * application, the code will look similar. You still need to register your
 * worker factories through WorkerFactoryCollection and then inject them into
 * the Disqontrol class.
 * The main difference is that you don't need to set up the worker environment,
 * because that's already set up - that is your application. You just need to
 * return it.
 *
 * The worker environment setup might look simply like this
 */
$workerEnvironmentSetup = function() use ($serviceContainer) {
    return $serviceContainer;
};

/**
 * At the end, instead of returning the Disqontrol instance, save it inside
 * your service container, for example. You can then use it as a dependency
 * for your own classes.
 */
$serviceContainer->set('disqontrol', $disqontrol);
