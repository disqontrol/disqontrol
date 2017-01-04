# Disqontrol

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

## Introduction

Disqontrol is a user-friendly framework for running background jobs in any
language.

Imagine your user has just uploaded a large picture and you want to create four
other pictures from it. But the pictures are not needed immediately and you
don't want to keep the user waiting for the server response.

Instead of processing the picture right now and delaying the response
to the user, you create a background job and respond to the user
without waiting for the result. The background job will be picked up by another
program that will process the picture without delaying your main application.

There are five steps in this process:

1. Creating a background job
2. Storing it
3. Picking it up from the storage
4. Processing it
5. Handling failed jobs

Where does Disqontrol come in?

Ad 1. Disqontrol helps you create a new background job, either from PHP
by using the Disqontrol producer, or from a command line. You can also add
the job with your own preferred library.

Adding a job in PHP:
``` php
$producer->add(new Job('example-pic.png', 'resize-pic'));
```
Adding a job from the command line:
``` bash
disqontrol addjob resize-pic '"example-pic.png"'
```

Ad 2. The job is stored in Disque, a specialized job queue created
by the author of Redis.

Ad 3. The job is picked up by a Disqontrol consumer, a long-running process
that listens to Disque, picks up jobs coming in and decides who should
process them.

Configuration:
``` yaml
queues:
    'resize-pic':
        worker:
            type: isolated_php_worker
            name: 'resize_pic_worker'
```

Ad 4. The code for processing a job - the worker - is ultimately your
responsibility, but Disqontrol provides a helpful scaffolding to write your
own workers, so you can just concentrate on the processing itself. Just follow
the interface.

The simplest PHP worker for resizing pictures:
``` php
class ResizePicWorker implements WorkerInterface
{
    public function process(JobInterface $job)
    {
        $picFileName = $job->getBody();
        // Concrete implementation left out
        return $this->resize($picFileName);
    }
}
```

Ad 5. It's important to decide what to do with failed jobs. Even though
the jobs are running in the background, some of them must be processed, some
make only sense for a certain amount of time and some of them are optional.
Disqontrol helps you configure failure handling for each job type individually,
as well as set a reasonable default behavior.

## Features

Disqontrol is written in PHP and uses [Disque](https://github.com/antirez/disque) as its underlying job queue.

With Disqontrol you get the following features:

- [Process jobs in any programming language](#processing-jobs-in-languages-other-than-php)
- [Schedule jobs and run them regularly](#scheduled-jobs)
- [Write PHP workers with all the scaffolding already in place](#using-php-workers)
- [Handle failures robustly](#what-happens-with-failed-jobs)
- [Process new jobs immediately for development and debugging](#debugging-jobs-in-a-synchronous-mode)
- Run multiple jobs from one queue in parallel
- Switch automatically to the best Disque node

Workers can be called via a command line and can therefore be written in any
language. The library also provides convenient scaffolding for workers written
in PHP.

The goal of Disqontrol is to be a user-friendly, clean and robust tool.

Disqontrol follows semantic versioning.

## Basic structure

We have tried to make the terminology as clear as possible.
We have taken into account how others, especially Disque, use the terms.

- `Job` is anything you need to identify the work you need to do. It can be
a simple integer ID or a whole array of data.
- `Queue` is a channel on which `Jobs` of a particular type are published. E.g.
'email-registration' or 'avatar-resize'.
- `Producer` is a class or a command you use in your application to add new jobs
to the queue.
- `Consumer` is a long-running process that listens to queues, picks up jobs,
calls workers and decides what to do with failed jobs.
- `Worker` is a code that receives the job and does the actual work.
A worker can be PHP code or a console command.
- `Supervisor` is the top level command that ties it all together. It loads
the configuration and starts all `Consumers`.
- `Scheduler` is a command that takes care of repeated scheduled jobs.

## Usage

- [Requirements](#requirements)
- [Installation](#installation)
- [Getting started](#getting-started)
- [Adding new jobs](#adding-new-jobs)
- [Processing jobs](#processing-jobs)
- [What happens with failed jobs?](#what-happens-with-failed-jobs)
- [Scheduled jobs](#scheduled-jobs)
- [Debugging jobs in a synchronous mode](#debugging-jobs-in-a-synchronous-mode)

### Requirements

- PHP 5.5+
- [Disque](https://github.com/antirez/disque)
- Function `proc_open()` allowed
- Suggested: PHP extension PCNTL for a graceful shutting down of consumers

### Installation

Install Disqontrol with the PHP package manager, [Composer](https://getcomposer.org/):

``` bash
composer require disqontrol/disqontrol
```

### Getting started

Copy the file `docs/examples/disqontrol.yml.dist` to `disqontrol.yml`, open `disqontrol.yml`
and configure Disqontrol. You need to fill out these sections:

- `disque` contains the information about the connection to Disque
- `queues` configures the queues and what worker should process the jobs
for each queue. You can leave all parameters as default and just configure
the worker for each queue.
- `consumers` - you can leave the whole section empty for starters, Disqontrol
will spawn consumers with the default parameters.

Start Disqontrol:

``` bash
/path/to/disqontrol supervisor
```

#### Using Disqontrol in your PHP application

In your application, all Disqontrol functions are accessible from an instance of
`Disqontrol\Disqontrol` that you need to create. It can be as simple as

``` php
$pathToConfig = '/path/to/disqontrol.yml';
$disqontrol = new Disqontrol\Disqontrol($pathToConfig);
```

See `docs/examples/app_bootstrap.php`

If you use PHP workers, the setup is a bit more involved. See the documentation
section "[Using PHP Workers](#using-php-workers)".

### Adding new jobs

#### Adding jobs in PHP with Disqontrol

Create a new job and send it to the job producer.

``` php
$job = new Disqontrol\Job\Job('job-body', 'queue');

$producer = $disqontrol->getProducer();
$producer->add($job);
```

If you don't want the job to be processed as soon as possible, you can delay it.

``` php
$delay = 3600; // in seconds, so this job will be enqueued in one hour
$producer->add($job, $delay);
```

#### Adding jobs from the command line with Disqontrol

You can add jobs via a console command.

``` bash
disqontrol addjob queue '"JSON-serialized job body"'
```

If you don't want the job to be processed as soon as possible, you can delay it.

``` bash
disqontrol addjob queue '"JSON-serialized job body"' --delay=3600
```

#### Adding jobs with other libraries

You can add jobs with other Disque libraries. Just JSON-serialize the job body.

### Processing jobs

#### Using PHP workers

PHP workers are workers (code that processes jobs) written in PHP and called
directly by Disqontrol.

You can of course write workers in any language (including PHP) and call them
via the command line. These command-line workers are completely independent
of Disqontrol.

But because Disqontrol is written in PHP, it offers convenient scaffolding
to write PHP workers more quickly and easily.

##### Inline vs. isolated PHP workers

There are two types of PHP workers - inline PHP workers and isolated PHP workers.

The difference between them is simple: If the Consumer, the long-running
process that listens for new jobs, receives a job that should be processed
by an inline PHP worker, the worker is executed directly in the Consumer process.

If the job should be processed by an isolated PHP worker, the worker is called
in a separate process created only for this one job.

The advantage of isolated PHP workers is that there can be no memory leaks.
The worker exits after it processes the job.

The advantage of inline PHP workers is that they are potentially faster and
require less computing capacity. Unlike isolated PHP workers they don't
have to set up the environment (DB connection etc.) over and over again.

It's up to you to make informed trade-offs. PHP workers running just with
few dependencies, in a lightweight environment, are faster when called
inline. Heavy workers that need a connection to the DB, to the cache and
to other external services, are safer to run in separate processes.

Fortunately you can switch between using isolated and inline PHP workers just by
changing a single word in the configuration file and easily test what's better
for your particular situation.

##### Writing PHP workers

PHP workers must implement the `Disqontrol\Worker\WorkerInterface`.
Each worker needs its own factory. A factory's purpose is to return the worker.
Worker factories must implement the `Disqontrol\Worker\WorkerFactoryInterface`.

Worker factories and workers live outside of your application and don't have
an immediate access to your application environment.

"Environment" in this case means the connection to the database and to the cache,
the configuration and service container etc.

To provide your workers and worker factories with the environment, write
a piece of code that sets up your environment. It can be as short as

``` php
require_once 'my_app_bootstrap.php';
```

Wrap it in an anonymous function and make it return the entry point
into your application environment, most likely a service container:

``` php
$environmentSetup = function() {
    // In this case, my_app_bootstrap.php must end with a return statement
    $serviceContainer = require_once 'my_app_bootstrap.php';
    return $serviceContainer;
}
```

The `WorkerFactoryInterface` that all worker factories must implement has
a special method signature:

``` php
public method create($workerEnvironment, $workerName);
```

Your worker factories live outside of your code, but before they are asked
to return a worker, they will receive your environment. What they receive is
exactly what you returned from the anonymous function above (in our example it
would be the variable `$serviceContainer`).

Why is the environment setup code separate?

The reason for this is that Disqontrol starts a few long running processes.
In order for them to be as small as possible and in order to minimize memory
leaks, we don't want to start up the whole application environment
(DB connection etc.) unless it's absolutely necessary. For example
the Disqontrol Supervisor doesn't need your application environment at all.
And only some Consumers are going to need it - only those that are configured
to call inline PHP workers.

To summarize:

The environment setup code is registered with Disqontrol, but it is only
called when it is needed - and only once. Its result is then injected into each
worker factory.

##### Registering PHP workers and the environment setup code in Disqontrol

If you want to use PHP workers - whether called inline directly in the Consumer,
or in a separate process - you need to connect your PHP application
and Disqontrol.

Disqontrol is used in two ways:
- It is a library that you use in your PHP application
- And it is itself an application run via the command line

You must connect them in both of these cases.

The connection has 4 steps:

1. Create a `WorkerFactoryCollection`
2. Write and register a code that sets up the environment for your PHP workers.
This is described in the previous section.
3. Create and register worker factories for all your PHP workers
4. Create a new Disqontrol instance where you put it all together

An abridged version could look like this:

``` php
$workerFactoryCollection = new Disqontrol\Worker\WorkerFactoryCollection();

$workerEnvironmentSetup = function() {
    $serviceContainer = require_once 'my_app_bootstrap.php';
    return $serviceContainer;
};
$workerFactoryCollection->registerWorkerEnvironmentSetup($workerEnvironmentSetup);

$picResizeWorkerFactory = new ExampleWorkerFactory();
$workerFactoryCollection->addWorkerFactory('pic_resize_worker', $picResizeWorkerFactory);

$pathToConfig = '/path/to/disqontrol.yml';
$debug = true;
$disqontrol = new Disqontrol\Disqontrol($pathToConfig, $workerFactoryCollection, $debug);
return $disqontrol;
```

Save this code in a file, let's call it the Disqontrol bootstrap file.

For a longer and commented example, see `docs/examples/disqontrol_bootstrap.php`

When running Disqontrol as a command line application, tell it what bootstrap
file it should use by adding the argument `--bootstrap`:

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

#### Processing jobs in languages other than PHP

Disqontrol can call your workers via the command line.

In the configuration file, use the type `command` and specify the exact command
you want to call.

An example configuration:
``` yaml
queues:
    'registration-email':
        worker:
            type: command
            command: php console email:send
```

Console commands are called with these added arguments:
- `--queue=` followed by the queue name
- `--body=` followed by a JSON-serialized job body.
- `--metadata=` followed by JSON-serialized job metadata

The exact command for this worker would be:
``` bash
php console email:send --queue='registration-email' --body=... --metadata=...
```

If the console command returns an exit code `0`, the job is considered
to be succesfully processed. All other exit codes mean that the job failed.

### What happens with failed jobs?

Failed jobs are returned to the queue with an ever longer, slightly randomized
delay. This behavior is called "[exponential backoff](https://cloud.google.com/storage/docs/exponential-backoff)" and it is used so that
Disque doesn't get slammed by many re-enqueued jobs if something goes wrong.

The exponential backoff in Disqontrol will retry a failed job

 - 2 times in the first minute
 - 5 times in the first hour
 - 7 times in the first 24 hours
 - following about one retry a day
 - for a total of 37 retries in 30 days

When the number of retries of a failed job reaches a certain configurable number
and the job still wasn't successfully processed, it is moved to a queue
for failed jobs (also called "a failure queue" or "a dead letter queue"), where
you can inspect it. Jobs will stay in the failure queue until Disque deletes
them, which is around 45 days.

You can configure

- the maximum number of retries and
- the failure queue

in the configuration for all queues together as well as for each queue
individually.

All failures are also logged in the log file specified in the configuration.

### Scheduled jobs

#### One-off scheduled jobs

To schedule a job that occurs just once, calculate by how many seconds the job
should be delayed and add it with this delay.

Scheduling a job by delaying it in PHP:
``` php
$delay = 3600; // This job will be enqueued in one hour
$producer->add($job, $delay);
```

Scheduling a job by delaying it via the command line:
``` bash
disqontrol addjob queue '"job body"' --delay=3600
```

##### Repeated scheduled jobs

Disqontrol supports repeated scheduled jobs. To set up jobs to run regularly,
you have to

- Create a Disqontrol crontab
- Run the Disqontrol scheduler every minute from the system cron

Running scheduled jobs with Disqontrol allows you to version your crontab
in your code repository and deploy the changes simply by deploying your code.
If you don't need this, you can of course schedule repeated jobs directly
in the system crontab.

A Disqontrol crontab row has the following syntax:

```
* * * * * queue job-body
```

Where
- The asterisks follow the common cron syntax (minute, hour, day, month, weekday),
- `queue` is the name of the job queue and
- `job-body` is the body of the scheduled job. The body can contain white spaces.

An example crontab with repeated jobs may look like this:

```
15 5 * * * daily-cleanup 1
34 2 * * 5 weekly-pruning all
*/5 * * * * five-minute-checkup 1
```

The first job will run every day at 05:15 AM, the second job will run every
Friday at 02:34 AM and the third job will run every 5 minutes.

See also `docs/examples/crontab`

Run the scheduler every minute by adding this entry to your system crontab:

``` bash
* * * * * /path/to/disqontrol scheduler --crontab=/path/to/crontab >/dev/null 2>&1
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
The `processjob` command doesn't accept the `delay` parameter, because it makes
no sense here.

### Roadmap

The following features are planned for the 1.0 release:

- HTTP workers (a job can be processed by an HTTP call)
- Autoscaling of consumers ("just" the algorithm is missing, see
`Disqontrol\Consumer\Autoscale\PredictiveAutoscaling`)
- the `--config` parameter (for installations not using the bootstrap file,
eg. teams not writing PHP code)

### Why Disque?

There are many general message queues - RabbitMQ, ZeroMQ, IronMQ, Amazon SQS.
Redis also has a queue functionality. Some libraries even offer databases as
queue backends.

General message queues treat a job just like a message and the job workflow
must be simulated in libraries.

Each queue implementation has different features and weaknesses. Queue libraries
usually allow you to choose from different backends and try to make up for
missing features in the code. This leads to the problem of a common denominator,
unnecessary complexity and doesn't allow one to use the features of one queue
fully.

That's why we have decided to use just one queue - Disque - written
by the developer of Redis.

For the above mentioned reasons, we have no plans to support other queues.
Instead we want to fully use all features of Disque.

### Alternatives

For similar libraries in PHP, have a look at 

- [Bernard](https://bernard.readthedocs.org/)
- [PHP-Queue](https://github.com/CoderKungfu/php-queue)
- [php-resque](https://github.com/chrisboulton/php-resque)

For libraries in other languages, have a look at

- [Resque](https://github.com/resque/resque) in Ruby
- [Celery](https://celery.readthedocs.org/en/latest/) in Python

## Change log

Please see the [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email info@mediaplus.cz instead of using the issue tracker.

## Graceful handover

"When you lose interest in a program, your last duty to it is to hand it off to
a competent successor."  
\- Eric S. Raymond, The Cathedral and the Bazaar

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

- [All Contributors][link-contributors]
- Big thanks to Antirez for [Disque](https://github.com/antirez/disque)
- Big thanks to Mariano for [Disque-php](https://github.com/mariano/disque-php)
- Thanks to Martin Patera for kicking off the work

## License

The MIT License (MIT). Please see the [License](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/disqontrol/disqontrol.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/disqontrol/disqontrol/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/disqontrol/disqontrol.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/disqontrol/disqontrol.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/disqontrol/disqontrol.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/disqontrol/disqontrol
[link-travis]: https://travis-ci.org/disqontrol/disqontrol
[link-scrutinizer]: https://scrutinizer-ci.com/g/disqontrol/disqontrol/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/disqontrol/disqontrol
[link-downloads]: https://packagist.org/packages/disqontrol/disqontrol
[link-author]: https://github.com/disqontrol
[link-contributors]: ../../contributors
