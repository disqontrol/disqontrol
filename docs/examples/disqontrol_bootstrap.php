<?php
/**
 * This is an example bootstrap file for Disqontrol
 *
 * It serves to bridge your application and Disqontrol for cases where Disqontrol
 * runs as a command line application.
 */
require_once __DIR__ . '/vendor/autoload.php';

// See docs/examples/DisqontrolFactory.php
$disqontrolFactory = new DisqontrolFactory();

$debug = true;
$disqontrol = $disqontrolFactory->create($debug);

// Don't forget to return the Disqontrol instance on the last line
return $disqontrol;

/**
 * For connecting to Disqontrol from your application, see
 * docs/examples/app_bootstrap.php
 */
