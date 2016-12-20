<?php
/**
 * When you need to connect to Disqontrol from your application, it's similar
 * to docs/examples/disqontrol_bootstrap.php.
 *
 * If you use PHP workers, you too need to register your worker factories
 * through WorkerFactoryCollection and then inject them into the Disqontrol
 * object.
 *
 * The main difference is that you don't need to set up the worker environment,
 * because that's already set up - that is your application. You just need to
 * return it.
 *
 * At the end of your application bootstrap, create a Disqontrol instance and
 * save it for further use, for example in your service container. You can then
 * use it as a dependency for your own classes.
 */

// See docs/examples/DisqontrolFactory.php
$disqontrolFactory = new DisqontrolFactory();

$debug = true;
$disqontrol = $disqontrolFactory->create($debug);
$serviceContainer->set('disqontrol', $disqontrol);
