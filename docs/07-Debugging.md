# Disqontrol

- [Introduction](index.md)
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
- **Debugging jobs in a synchronous mode**

## Debugging jobs in a synchronous mode

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

