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
- **What happens with failed jobs?**
- [Debugging jobs in a synchronous mode](07-Debugging.md)

## What happens with failed jobs?

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
