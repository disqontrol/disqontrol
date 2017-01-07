# Disqontrol

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
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

- [Process jobs in any programming language](docs/04-ProcessingJobs.md#processing-jobs-in-languages-other-than-php)
- [Schedule jobs and run them regularly](docs/05-SchedulingJobs.md)
- [Write PHP workers with all the scaffolding already in place](docs/04-ProcessingJobs.md#using-php-workers)
- [Configure the lifecycle of a job in detail](docs/02-Configuration.md#queue-defaults)
- [Handle failures robustly](docs/06-FailureHandling.md)
- [Process new jobs immediately for development and debugging](docs/07-Debugging.md)
- Run multiple jobs from one queue in parallel
- Switch automatically to the best Disque node

Workers can be called via a command line and can therefore be written in any
language. The library also provides convenient scaffolding for workers written
in PHP.

The goal of Disqontrol is to be a user-friendly, clean and robust tool.

Disqontrol follows semantic versioning.

## Documentation

- [Introduction](docs/index.md#introduction)
- [Features](docs/index.md#features)
- [Terminology](docs/index.md#terminology)
- [Requirements](docs/01-GettingStarted.md#requirements)
- [Installation](docs/01-GettingStarted.md#installation)
- [Getting started](docs/01-GettingStarted.md#getting-started)
- [Configuration](docs/02-Configuration.md)
- [Adding new jobs](docs/03-AddingJobs.md)
- [Processing jobs](docs/04-ProcessingJobs.md)
- [Scheduled jobs](docs/05-SchedulingJobs.md)
- [What happens with failed jobs?](docs/06-FailureHandling.md)
- [Debugging jobs in a synchronous mode](docs/07-Debugging.md)

## Why Disque?

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

## Roadmap

The following features are planned for the 1.0 release:

- [x] Consumers pick up jobs and call workers
- [x] Supervisor keeps Consumers alive
- [x] Consumers quit gracefully
- [x] Isolated and inline PHP workers
- [x] Command-line workers
- [x] Failing jobs re-enqueued with exponential backoff
- [x] Disqontrol has access to a PHP app environment
- [x] Scheduled repeated jobs
- [x] Synchronous mode
- [ ] HTTP workers (a job can be processed by an HTTP call)
- [ ] Autoscaling of consumers ("just" the algorithm is missing, see
`Disqontrol\Consumer\Autoscale\PredictiveAutoscaling`)
- [ ] Using custom failure strategies (see `Disqontrol\Dispatcher\JobDispatcher`
and `Disqontrol\Dispatcher\Failure\FailureStrategyCollection`)
- [ ] The `--config` parameter (for installations not using the bootstrap file,
eg. teams not writing PHP code)

## Change log

Please see the [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [All Contributors][link-contributors]
- Big thanks to Antirez for [Disque](https://github.com/antirez/disque)
- Big thanks to Mariano for [Disque-php](https://github.com/mariano/disque-php)
- Thanks to Martin Patera for kicking off the work

## Alternatives

For similar libraries in PHP, have a look at 

- [Bernard](https://bernard.readthedocs.org/)
- [PHP-Queue](https://github.com/CoderKungfu/php-queue)
- [php-resque](https://github.com/chrisboulton/php-resque)
- [php-enqueue](https://github.com/php-enqueue/enqueue-dev)

For libraries in other languages, have a look at

- [Resque](https://github.com/resque/resque) in Ruby
- [Celery](https://celery.readthedocs.org/en/latest/) in Python

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
