# Disqontrol

- [Introduction](index.md)
- [Features](index.md#features)
- [Terminology](index.md#terminology)
- **Requirements**
- [Installation](01-GettingStarted.md#installation)
- [Getting started](01-GettingStarted.md#getting-started)
- [Configuration](02-Configuration.md)
- [Adding new jobs](03-AddingJobs.md)
- [Processing jobs](04-ProcessingJobs.md)
- [Scheduled jobs](05-SchedulingJobs.md)
- [What happens with failed jobs?](06-FailureHandling.md)
- [Debugging jobs in a synchronous mode](07-Debugging.md)

## Requirements

- PHP 5.5+
- [Disque](https://github.com/antirez/disque)
- Function `proc_open()` allowed
- Suggested: PHP extension PCNTL for a graceful shutting down of consumers

## Installation

Install Disqontrol with the PHP package manager, [Composer](https://getcomposer.org/):

``` bash
composer require disqontrol/disqontrol
```

## Getting started

Copy the file `docs/examples/disqontrol.yml.dist` to `disqontrol.yml`, open
`disqontrol.yml` and configure Disqontrol. If you just want to play around,
the only section you need to change is `disque`, which contains the information
about the connection to Disque.

For more details see [Configuration](02-Configuration.md).

Start Disqontrol and you're on:

``` bash
/path/to/disqontrol supervisor
```

### Using Disqontrol in your PHP application

In your application, all Disqontrol functions are accessible from an instance of
`Disqontrol\Disqontrol` that you need to create. It can be as simple as

``` php
$pathToConfig = '/path/to/disqontrol.yml';
$disqontrol = new Disqontrol\Disqontrol($pathToConfig);
```

See `docs/examples/app_bootstrap.php`

If you use PHP workers, the setup is a bit more involved. See the documentation
section "[Using PHP Workers](04-ProcessingJobs.md#using-php-workers)".

