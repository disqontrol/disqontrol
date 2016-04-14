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

- Run multiple jobs in parallel
- Process jobs in any programming language
- Run certain jobs regularly
- Handle failures robustly and log as much as you need
- Switch automatically to the best Disque node
- Use synchronous mode for debugging (process new jobs immediately)

Workers can be called via a console command or a HTTP request and can therefore
be written in other languages than PHP. The library also provides convenient
wrappers for workers written in PHP.

The goal of Disqontrol is to be a user-friendly, clean and robust tool.

Disqontrol follows semantic versioning.


### Why?

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

### Terminology

If you dive into the world of queues, one thing becomes immediately apparent.
The terminology is all over the place. General message queues tend to talk
about Publishers, Subscribers and Messages while job queues tend to use Producers,
Consumers and Jobs (or Tasks). Some libraries don't distinguish the long-running
process listening to the queue from the code working on one particular job and
talk about Workers spawning child Workers. And yet others make up their own 
unique names and call Producers "Dispatchers".

It's chaos.

We have thought long and hard about the terminology and tried to make it as clear
as possible. We have taken into account how others, especially Disque, use
the words.

`Job` is anything you need to identify the work you need to do. It can be
a simple integer ID or a whole array of data.
`Queue` is a channel on which `Jobs` of a particular type are published. E.g.
'email-registration' or 'avatar-resize'. It can also mean the whole of Disque.
`Producer` is a class or a command you use in your application to add jobs
to the queue for later processing.
`Consumer` is a long-running process that listens to one or more queues,
fetches jobs from them, calls workers and decides what to do with failed jobs.
`Worker` is the code that receives the job and does the actual work.
A worker can be PHP code called directly by the `Consumer`, a console command
or a service listening for HTTP calls (e.g. a REST API).
`Supervisor` is the top level command that ties it all together. It loads
the configuration and starts all `Consumers` as needed.

These are the basic terms you need to use Disqontrol. Inside Disqontrol there
are a few more terms that will be explained where needed.

### Usage



### Failed jobs



### Extending the functionality via Events



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

## Install

Via Composer

``` bash
$ composer require webtrh/disqontrol
```

## Usage

``` php
$disqontrol = new Disqontrol\Disqontrol();
```

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
