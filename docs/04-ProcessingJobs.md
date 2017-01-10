# Disqontrol

- [Introduction](index.md)
- [Features](index.md#features)
- [Terminology](index.md#terminology)
- [Requirements](01-GettingStarted.md#requirements)
- [Installation](01-GettingStarted.md#installation)
- [Getting started](01-GettingStarted.md#getting-started)
- [Configuration](02-Configuration.md)
- [Adding new jobs](03-AddingJobs.md)
- **Processing jobs**
  - [Using PHP workers](#using-php-workers)
    - [Inline vs. isolated PHP workers](#inline-vs-isolated-php-workers)
    - [Writing PHP workers](#writing-php-workers)
    - [Registering PHP workers and the environment setup code in Disqontrol](#registering-php-workers-and-the-environment-setup-code-in-disqontrol)
  - [Processing jobs in languages other than PHP](#processing-jobs-in-languages-other-than-php)
- [Scheduled jobs](05-SchedulingJobs.md)
- [What happens with failed jobs?](06-FailureHandling.md)
- [Debugging jobs in a synchronous mode](07-Debugging.md)


## Processing jobs

### Using PHP workers

PHP workers are workers (code that processes jobs) written in PHP and called
directly by Disqontrol.

You can of course write workers in any language (including PHP) and call them
via the command line. These command-line workers are completely independent
of Disqontrol.

But because Disqontrol is written in PHP, it offers convenient scaffolding
to write PHP workers more quickly and easily.

#### Inline vs. isolated PHP workers

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

#### Writing PHP workers

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

#### Registering PHP workers and the environment setup code in Disqontrol

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
$pathToBootstrap = '/path/to/disqontrol_bootstrap.php';
$debug = true;
$disqontrol = new Disqontrol\Disqontrol(
    $pathToConfig,
    $workerFactoryCollection,
    $debug,
    $pathToBootstrap
);
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

### Processing jobs in languages other than PHP

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

