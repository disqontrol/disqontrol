# Disqontrol

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

A user-friendly framework for running background jobs in any language.

Disqontrol is written in PHP and uses [Disque](https://github.com/antirez/disque) as the job queue.

With Disqontrol you get the following features:

- Process jobs in any programming language
- Run jobs regularly
- Handle failures robustly and log as much as you need
- Run multiple jobs from one queue in parallel
- Switch automatically to the best Disque node
- Switch to synchronous mode for debugging (process new jobs immediately)

Workers can be called via a command line or an HTTP request and can therefore
be written in any language. The library also provides convenient helpers
for workers written in PHP.

The goal of Disqontrol is to be a user-friendly, clean and robust tool.

Disqontrol follows semantic versioning.

## Basic structure

We have thought about the terminology and tried to make it as clear as possible.
We have taken into account how others, especially Disque, use the words.

`Job` is anything you need to identify the work you need to do. It can be
a simple integer ID or a whole array of data.
`Queue` is a channel on which `Jobs` of a particular type are published. E.g.
'email-registration' or 'avatar-resize'. It can also mean the whole Disque.
`Producer` is a class or a command you use in your application to add jobs
to the queue for later processing.
`Consumer` is a long-running process that listens to one or more queues,
fetches jobs from them, calls workers and decides what to do with failed jobs.
`Worker` is the code that receives the job and does the actual work.
A worker can be PHP code called directly by the `Consumer`, a console command
or a service listening for HTTP calls (e.g. a REST API).
`Supervisor` is the top level command that ties it all together. It loads
the configuration and starts all `Consumers` as needed.
`Scheduler` is a command that takes care of scheduled tasks. It should run
every minute via cron.

These are the basic terms you need to use Disqontrol. Inside Disqontrol there
are a few more terms that will be explained where needed.

## Usage

### Installation

Install Disqontrol via Composer:

``` bash
composer require webtrh/disqontrol
```

### Getting started

Copy the file `docs/examples/disqontrol.yml.dist` to `disqontrol.yml`, open `disqontrol.yml`
and configure Disqontrol. You need to fill out these sections:

- `disque` contains the information about the connection to Disque
- `queues` configures the queues and what worker should process the jobs
for each queue. You can leave all parameters as default and configure
just the worker for each queue.
- `consumers` - you can leave the whole section empty for starters, Disqontrol
will spawn consumers with the default parameters.

Start Disqontrol:

``` bash
/path/to/disqontrol supervisor
```

If in a PHP application, create a Disqontrol instance:

``` php
$disqontrol = new Disqontrol\Disqontrol('/path/to/configuration');
```

### Adding jobs to Disque

#### From a PHP application

Create a new job and send it to the job producer.

``` php
$job = new Disqontrol\Job\Job('queue', 'job-body');

$producer = $disqontrol->getProducer();
$producer->add($job);
```

If you don't want the job to be processed as soon as possible, you can delay it.

``` php
$delay = 3600; // in seconds, so this job will be enqueued in one hour
$producer->add($job, $delay);
```

#### From a non-PHP application

You can add jobs via a console command.

``` bash
disqontrol addjob queue '"JSON-serialized job body"'
```

If you don't want the job to be processed as soon as possible, you can delay it.

``` bash
disqontrol addjob queue '"JSON-serialized job body"' --delay=360
```

### Debugging jobs in a synchronous mode

In order to debug jobs during development, you can add jobs synchronously.
This will skip the job queue altogether and process the job immediately.

You can switch to a synchronous mode easily. In your PHP application, instead
of calling
``` php
$disqontrol->getProducer()->add($job);
```

call
``` php
$synchronousMode = true;
$disqontrol->getProducer($synchronousMode)->add($job);
```

Failed jobs in the synchronous mode will be logged and thrown away
(ie. not repeated).

Unlike the normal producer, which cannot know the result, the synchronous
producer returns the result of the processing of the job:

``` php
$result = $synchronousProducer->add($job);
```

If you're adding jobs via a console command, instead of

``` bash
disqontrol addjob
```

use the mirror command

``` bash
disqontrol processjob
```

It takes the same arguments as `addjob`, a queue and a JSON-serialized job body.

### Regular, repeated jobs

Disqontrol supports regular, repeated jobs. To set up regular jobs, you have to
- create a Disqontrol crontab
- run the Disqontrol scheduler every minute from the system cron

You can also of course use the system crontab. Running scheduled jobs over
Disqontrol allows you to version your crontab in your code repository
and deploy the changes simply by deploying your code.

A Disqontrol crontab row has the following syntax:

`* * * * * queue job-body`

Where
- the asterisks follow the common cron syntax (minute, hour, day, month, weekday),
- `queue` is the name of the job queue and
- `job-body` is the body of the scheduled job. The body can contain white spaces.

An example crontab with regular jobs may look like this:

``` bash
15 5 * * * daily-cleanup 1
34 2 * * 5 weekly-pruning all
*/5 * * * * five-minute-checkup 1
```

The first job will run every day at 05:15 AM, the second job will run every
Friday at 02:34 AM and the third job will run every 5 minutes.

Run the scheduler every minute by adding this entry to your system crontab:

``` bash
* * * * * /path/to/disqontrol scheduler --crontab=/path/to/crontab >/dev/null 2>&1
```

### What happens with failed jobs?


### Using PHP workers

PHP workers are workers (code that processes jobs) written in PHP and called
directly via Disqontrol.

You can of course write workers in any language (including PHP) and call them
via the command line (or HTTP), but we call these "Command-Line/HTTP workers" - they are
completely independent of Disqontrol.

But because Disqontrol is written in PHP, it offers a few helper features
to write PHP workers more quickly and easily.


#### Inline vs. isolated PHP workers

There are two types of PHP workers - inline PHP workers and isolated PHP workers.

The difference between them is simple: If the Consumer, the long-running
process that listens for new jobs, receives a job that should be processed
by an inline PHP worker, the worker is called directly in the Consumer process.

If the job should be processed by an isolated PHP worker, the worker is called
in a separate process created only for this one job.

The advantage of isolated PHP workers is that there can be no memory leaks.
The worker exits after it processes the job.

The advantage of inline PHP workers is that they are potentially faster and
require less computing capacity. Unlike isolated PHP workers they don't
have to set up the environment (DB connection etc.) over and over again.

It's up to you to choose the tradeoffs. PHP workers running just with
few dependencies, in a lightweight environment, are probably better off
when called inline. Heavy workers that need a connection to the DB, to the cache
and to other external services, are probably safer to run in separate processes.

Fortunately you can switch between using isolated and inline PHP workers just by
changing a single word in the configuration file and easily test what's better
for your particular situation.


#### Writing PHP workers

PHP workers must implement the `Disqontrol\Worker\WorkerInterface`.
Each worker needs its own factory. A factory's purpose is to return a worker.
Worker factories must implement the `Disqontrol\Worker\WorkerFactoryInterface`.

Worker factories and workers live outside of your application and don't have
an immediate access to your application environment.

"Environment" in this case means the connection to the database and to the cache,
the configuration and service container etc.

To provide your workers and worker factories with the environment, write
a piece of code that sets up your environment. It can be as short as

``` php
require_once 'my_application_bootstrap.php';
```

Wrap it in an anonymous function and make it return the entry point
into your application environment, most likely a service container:

``` php
$environmentSetup = function() {
    require_once 'my_application_bootstrap.php';
    global $serviceContainer;
    return $serviceContainer;
}
```

The WorkerFactoryInterface that all worker factories must implement has
a peculiar method signature:

``` php
public method create($workerEnvironment);
```

Your worker factories live outside of your code, but before they are asked
to return a worker, they will receive your environment. What they receive is
exactly what we returned in the anonymous function above (in our example it
would be the variable `$serviceContainer`).

Why is the environment setup code separate and why is this so complicated?
The reason for this is that Disqontrol starts a few long running processes.
In order for them to be as small as possible and in order to minimize memory
leaks, we don't want to start up the whole application environment
(DB connection etc.) unless it's absolutely necessary. For example
the Disqontrol Supervisor doesn't need your application environment at all.
The Consumers may or may not need it (only those that must call inline PHP
workers).

To summarize:

The environment setup code is registered with Disqontrol, but it is only
called when it is needed (and only once). Its result is then injected into each
worker factory.

#### Registering PHP workers and the environment setup code in Disqontrol

If you want to use PHP workers - whether run inline directly in Consumer, 
or in a separate process - you need to connect your PHP application
and Disqontrol.

Disqontrol is used in two ways:
- It is a library that you use in your PHP application
- And it is itself an application run via the command line

You must create the connection for both of these cases.

The connection has 4 steps:

1. Instantiate a WorkerFactoryCollection
2. Write and register a code that sets up the environment for your PHP workers.
We talked about what the code should look like in the previous section.
3. Instantiate and register worker factories for all your PHP workers
4. Create a new Disqontrol instance where you put it all together

An abridged version could look like this:

``` php
$workerFactoryCollection = new Disqontrol\Worker\WorkerFactoryCollection();

$workerEnvironmentSetup = function() {
    require_once 'application_bootstrap.php';
    global $serviceContainer;
    return $serviceContainer;
};
$workerFactoryCollection->registerWorkerEnvironmentSetup($workerEnvironmentSetup);

$picResizeWorkerFactory = new ExampleWorkerFactory();
$workerFactoryCollection->addWorkerFactory('PicResizeWorker', $picResizeWorkerFactory);

$disqontrol = new Disqontrol\Disqontrol($configFile, $workerFactoryCollection, $debug);
return $disqontrol;
```

Save this code in a file, let's call it the Disqontrol bootstrap file.

NOTE: For a longer and commented example, see `docs/examples/disqontrol_bootstrap.php`

When running Disqontrol as a command line application, tell it what bootstrap
file it should use by adding the argument "--bootstrap":

``` bash
/path/to/disqontrol supervisor --bootstrap=/file/to/disqontrol_bootstrap.php
```

If you name the bootstrap file `disqontrol_bootstrap.php` and place it
in the working directory (the directory from which you call Disqontrol),
it will be used automatically, without the need to explicitly specify its path.
With the bootstrap file in its default location, you can then call just this:

```bash
/path/to/disqontrol supervisor
```

Disqontrol will look for `disqontrol_bootstrap.php` and use it automatically.

### Extending the functionality via Events


## Purpose

### Why Disqontrol?

When deciding to use a job queue, there are many small and large decisions 
to make. For example

- What job queue should I use?
- What should I do with failed jobs?
- How can I debug job queues during development?
- How can I support repeating tasks?
- Should I use long-running workers?

Disqontrol answers these questions for you and helps you implement a robust
job queue in your application, while giving you enough flexibility
to configure everything to your particular needs.

### Why Disque?

There are many general message queues - RabbitMQ, ZeroMQ, IronMQ, Amazon SQS.
Redis also has a queue functionality. Some libraries even offer databases as
queue backends. Despite this wealth of choice, there are just two main
job queues at this moment - Beanstalkd and Disque.

In general message queues, a job is treated just like a message and the job
workflow must be simulated in libraries.

Each queue implementation has different features and weaknesses. Queue libraries
usually allow you to choose from different backends and try to make up for
missing features in the code. This leads to the problem of a common denominator,
unnecessary complexity and doesn't allow one to use the features of one queue
fully.

That's why we have decided to use just one queue - Disque - written by the author
of Redis.

For the above mentioned reasons, we have no plans to support other queues.
Instead we want to use all features of Disque fully.

### Alternatives

For similar libraries in PHP, have a look at 

- [Bernard](https://bernard.readthedocs.org/)
- [PHP-Queue](https://github.com/CoderKungfu/php-queue)
- [php-resque](https://github.com/chrisboulton/php-resque)

For libraries in other languages, have a look at

- [Resque](https://github.com/resque/resque) in Ruby
- [Celery](https://celery.readthedocs.org/en/latest/) in Python

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email info@webtrh.cz instead of using the issue tracker.

## Graceful handover

"When you lose interest in a program, your last duty to it is to hand it off to
a competent successor."
- Eric S. Raymond, The Cathedral and the Bazaar

While we of course do not intend to abandon Disqontrol and hope to provide
a long-term continuity by engaging with the users and promoting maintainers, we
all know too many abandoned open source projects. People switch companies,
languages, and change their lifestyles, and ultimately we all die. Let us propose 
something similar to a prenuptial agreement in a marriage or an exit provision
in business contracts.

In case this library has been abandoned and you want to use and improve it,
please don't hesitate to ask us to add you as a maintainer.

If we don't respond timely, whoever is in charge of hosting this library and its
package metadata is allowed to appoint a new maintainer.

"Abandoned" means there has been no activity, no commit and no comment
in the library by any maintainer for at least 45 consecutive days, while there
have been new questions, comments or pull requests by non-maintainers.

We feel adding maintainers is a better solution than forking abandoned code
because it guarantees continuity in package metadata, all tickets, issues, and
pull requests.


## Credits

- [:author_name][link-author]
- [All Contributors][link-contributors]

Big thanks to Antirez for Disque and Mariano for Disque-php

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/webtrh/disqontrol.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/webtrh/disqontrol/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/thephpwebtrh/disqontrol.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/thephpwebtrh/disqontrol.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/webtrh/disqontrol.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/webtrh/disqontrol
[link-travis]: https://travis-ci.org/thephpwebtrh/disqontrol
[link-scrutinizer]: https://scrutinizer-ci.com/g/thephpwebtrh/disqontrol/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/thephpwebtrh/disqontrol
[link-downloads]: https://packagist.org/packages/webtrh/disqontrol
[link-author]: https://github.com/webtrh
[link-contributors]: ../../contributors
