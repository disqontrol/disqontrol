# Disqontrol

- **Introduction**
- [Features](index.md#features)
- [Terminology](index.md#terminology)
- [Requirements](01-GettingStarted.md#requirements)
- [Installation](01-GettingStarted.md#installation)
- [Getting started](01-GettingStarted.md#getting-started)
- [Configuration](02-Configuration.md)
- [Adding new jobs](03-AddingJobs.md)
- [Processing jobs](04-ProcessingJobs.md)
- [Scheduled jobs](05-SchedulingJobs.md)
- [What happens with failed jobs?](06-FailureHandling.md)
- [Debugging jobs in a synchronous mode](07-Debugging.md)

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

- [Process jobs in any programming language](04-ProcessingJobs.md#processing-jobs-in-languages-other-than-php)
- [Schedule jobs and run them regularly](05-SchedulingJobs.md)
- [Write PHP workers with all the scaffolding already in place](04-ProcessingJobs.md#using-php-workers)
- [Configure the lifecycle of a job in detail](02-Configuration.md#queue-defaults)
- [Handle failures robustly](06-FailureHandling.md)
- [Process new jobs immediately for development and debugging](07-Debugging.md)
- Run multiple jobs from one queue in parallel
- Switch automatically to the best Disque node

Workers can be called via a command line and can therefore be written in any
language. The library also provides convenient scaffolding for workers written
in PHP.

The goal of Disqontrol is to be a user-friendly, clean and robust tool.

Disqontrol follows semantic versioning.

## Terminology

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
