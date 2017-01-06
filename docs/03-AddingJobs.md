# Disqontrol

- [Introduction](index.md)
- [Features](index.md#features)
- [Terminology](index.md#terminology)
- [Requirements](01-GettingStarted.md#requirements)
- [Installation](01-GettingStarted.md#installation)
- [Getting started](01-GettingStarted.md#getting-started)
- [Configuration](02-Configuration.md)
- **Adding new jobs**
  - [Adding jobs in PHP with Disqontrol](#adding-jobs-in-php-with-disqontrol)
  - [Adding jobs from the command line with Disqontrol](#adding-jobs-from-the-command-line-with-disqontrol)
  - [Adding jobs with other libraries](#adding-jobs-with-other-libraries)
- [Processing jobs](04-ProcessingJobs.md)
- [Scheduled jobs](05-SchedulingJobs.md)
- [What happens with failed jobs?](06-FailureHandling.md)
- [Debugging jobs in a synchronous mode](07-Debugging.md)


## Adding new jobs

### Adding jobs in PHP with Disqontrol

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

### Adding jobs from the command line with Disqontrol

You can add jobs via a console command.

``` bash
disqontrol addjob queue '"JSON-serialized job body"'
```

If you don't want the job to be processed as soon as possible, you can delay it.

``` bash
disqontrol addjob queue '"JSON-serialized job body"' --delay=3600
```

### Adding jobs with other libraries

You can add jobs with other Disque libraries. Just JSON-serialize the job body.

