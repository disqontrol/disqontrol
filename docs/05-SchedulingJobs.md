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
- **Scheduled jobs**
  - [One-off scheduled jobs](#one-off-scheduled-jobs)
  - [Repeated scheduled jobs](#repeated-scheduled-jobs)
- [What happens with failed jobs?](06-FailureHandling.md)
- [Debugging jobs in a synchronous mode](07-Debugging.md)

## Scheduled jobs

### One-off scheduled jobs

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

### Repeated scheduled jobs

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

```
* * * * * /path/to/disqontrol scheduler --crontab=/path/to/crontab >/dev/null 2>&1
```
