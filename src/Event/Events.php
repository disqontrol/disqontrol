<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Event;

/**
 * This class describes all events emitted by Disqontrol
 *
 * @author Martin Schlemmer
 */
final class Events
{
    /**
     * After the startup of Disqontrol
     * Dispatched from Disqontrol
     */
    const INIT = 'disqontrol.init';

    /**
     * Before a producer adds a job
     * Dispatched from Producer::add()
     *
     * @see JobAddBeforeEvent
     */
    const JOB_ADD_BEFORE = 'disqontrol.job.add.before';

    /**
     * After a producer adds a job
     * Dispatched from Producer::add()
     *
     * @see JobAddAfterEvent
     */
    const JOB_ADD_AFTER = 'disqontrol.job.add.after';

    /**
     * After a consumer fetches a job (or more jobs)
     * Dispatched from Consumer
     */
    const JOB_FETCH = 'disqontrol.job.fetch';

    /**
     * Before routing a job (finding the right worker)
     * Dispatched from JobRouter
     */
    const JOB_ROUTE = 'disqontrol.job.route';

    /**
     * Before calling a worker
     * Dispatched from JobDispatcher
     *
     * Include information about the job and the worker
     */
    const JOB_PROCESS_BEFORE = 'disqontrol.job.process.before';

    /**
     * After the job worker has processed the job
     * Dispatched from JobDispatcher
     *
     * Include information about the result
     */
    const JOB_PROCESS_AFTER = 'disqontrol.job.process.after';

    /**
     * After the startup of a consumer
     * Dispatched from Consumer
     */
    const CONSUMER_START = 'disqontrol.consumer.start';

    /**
     * When checking whether there are enough consumer processes for the queues
     * Dispatched from Supervisor
     */
    const SUPERVISOR_STATUS_CHECK = 'disqontrol.supervisor.status.check';

    /**
     * After parsing all cron rules for scheduled, repeated jobs
     * Dispatched from ?
     */
    const CRON_CHECK = 'disqontrol.cron.check';

    /**
     * Before running the worker setup
     * Dispatched from WorkerRepository
     */
    const WORKER_SETUP = 'disqontrol.worker.setup';
}
