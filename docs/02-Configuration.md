# Disqontrol

- [Introduction](index.md)
- [Features](index.md#features)
- [Terminology](index.md#terminology)
- [Requirements](01-GettingStarted.md#requirements)
- [Installation](01-GettingStarted.md#installation)
- [Getting started](01-GettingStarted.md#getting-started)
- **Configuration**
  - [General settings](#general-settings)
  - [Disque](#disque)
  - [Queue defaults](#queue-defaults)
  - [Queues](#queues)
  - [Consumer defaults](#consumer-defaults)
  - [Consumers](#consumers)
- [Adding new jobs](03-AddingJobs.md)
- [Processing jobs](04-ProcessingJobs.md)
- [Scheduled jobs](05-SchedulingJobs.md)
- [What happens with failed jobs?](06-FailureHandling.md)
- [Debugging jobs in a synchronous mode](07-Debugging.md)

## Configuration

Use the file `docs/examples/disqontrol.yml.dist` as a starting point for your
configuration file. The example file is well documented and you can follow the
comments directly in there. It contains the following sections:

### General settings

Example:

``` yaml
log_dir: /var/log
cache_dir: /tmp/
```

#### `log_dir`

A path to the Disqontrol log file, relative to the current working
directory, or absolute.

#### `cache_dir`

A path where Disqontrol can save its cache files, relative to the
current working directory, or absolute.


### Disque

Example:

``` yaml
disque:
    - host: '127.0.0.1'
      port: 7711
      password: 'foo'
```

#### `host`

The IP address of a Disque node

#### `port`

The port of a Disque node

#### `password`

The password (optional)


### Queue defaults

This section sets the default values for all queues that don't set the values
explicitly.

Example:

``` yaml
queue_defaults:
    job_process_timeout: 600
    job_lifetime: 172800
    failure_strategy: retry
    max_retries: 25
    failure_queue: 'failed-jobs'
```

#### `job_process_timeout`

How long a job can be processed before it times out, in
seconds.

#### `job_lifetime`

How long a job can wait in the queue before getting
automatically deleted, in seconds.

The maximum allowed value is 3932100 seconds, or about 45,5 days.

#### `failure_strategy`

How failed jobs should be handled.

Possible values:

- `retry`: Retry the failed job with an exponential backoff, ie. the job will
be returned to the queue with an ever longer delay
- `retry_immediately`: Retry the failed job immediately, until the job runs out
of allowed retries.

#### `max_retries`

How many times a failed job should be retried before we give up and move it to
a failure queue.

#### `failure_queue`

A queue where failed jobs are moved to when they run out of retries.


### Queues

Define all existing queues, their parameters - if they differ from the queue
defaults - and their workers.

Example:

``` yaml
queues:
    'registration-email':
        worker:
            type: command
            command: 'php console email:send'
            
    'pic-resize':
        worker:
            type: inline_php_worker
            name: 'pic_resize_worker'
            
    'rss-update':
        job_process_timeout: 600
        job_lifetime: 60
        failure_strategy: retry_immediately
        max_retries: 1
        failure_queue: 'failed-rss-updates'
        worker:
            type: isolated_php_worker
            name: 'rss_update_worker'
```

#### Queue name

The key of each entry under `queues` is the name of the queue. It can contain
any parameter from the section `queue_defaults`.

#### `worker`

The start of the worker definition. Each queue must have a worker.

#### `type`

The type of the worker. Possible values:

- `command`: A CLI command
- `inline_php_worker`: A PHP worker called directly in the consumer
- `isolated_php_worker`: A PHP worker called in an isolated process

#### Worker address: `command`, `name`

These parameters are interchangeable and exist for better readability. They
designate the "address" of a worker: for PHP workers it's the name under which
they're registered, for command-line workers, it's the command that will be 
called in order to process the job.


### Consumer defaults

This section sets all default parameters for consumers that don't provide their
own parameters.

Example:

``` yaml
consumer_defaults:
    min_processes: 1
    max_processes: 5
    autoscale: true
    job_batch: 10
```

#### `min_processes`

The minimum number of processes the consumer should spawn.

#### `max_processes`

The minimum number of processes the consumer should spawn.

#### `autoscale`

Allow Disqontrol to spawn more consumers if there are many jobs coming in and
there's a demand for more.

#### `job_batch`

The maximum number of jobs processed in one consumer in one batch. The consumer
will ask Disque for a batch of as many jobs as specified here and if possible
(for workers that allow asynchronous calls) calls all workers in the batch at
once.

### Consumers

This section defines consumers and which queues they should consume. You can
define one consumer for each queue, one consumer for multiple queues, or even
leave this whole section empty, in which case Disqontrol will spawn just one
consumer for all queues defined above.

Example:

``` yaml
consumers:
    - queues:
        - 'registration-email'
        - 'profile-update'
      min_processes: 2
      max_processes: 10
      job_batch: 5

    - queues:
        - 'pic-resize'
```

#### `queues`

This is the only required parameter of a consumer. List all queues this consumer
should listen to.

You can also override any parameter from the `consumer_defaults` section. Be
mindful of the indentation. The parameters from the `consumer_defaults` must be
on the same indentation level as the word `queues`.

All queues that don't have their own consumer will be served by the default
Disqontrol consumer.
