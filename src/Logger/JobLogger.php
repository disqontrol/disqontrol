<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Logger;

use Disqontrol\Event\Events;
use Disqontrol\Event\LogJobDetailsEvent;
use Disqontrol\Job\JobInterface;
use Disqontrol\Job\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A logger that can add job debug information to the log messages
 *
 * We use the second argument, $context, to send jobs to the logger.
 * If the caller includes a job object in the $context, we presume it wants
 * to add details about the job, so we add a debug level message with the job
 * details.
 *
 * To add job details to any log message, just add this to the second argument,
 * $context:
 *
 * $context[JobLogger::JOB_INDEX] = $job;
 *
 * I have considered three solutions:
 * - Adding a Monolog processor and a handler
 * - Inheriting the Monolog logger and overriding parent methods
 * - Encapsulating the logger and calling it as a dependency
 *
 * I have ruled out the first solution, because we want to emit two messages
 * from one call and neither a handler or a processor are the right place.
 * I have tried the second solution, inheritance, but the constructor was
 * complex and unit testing was not easy without mocking the tested class.
 * In the end I have decided to use composition and encapsulate the original
 * logger.
 *
 * @author Martin Schlemmer
 */
class JobLogger implements LoggerInterface
{
    const JOB_INDEX = 'job';

    /**
     * The original Monolog logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param LoggerInterface          $logger The Monolog logger
     * @param SerializerInterface      $serializer
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = array())
    {
        $result = $this->logger->emergency($message, $context);
        $this->addJobDetails($context, $message, Logger::EMERGENCY);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = array())
    {
        $result = $this->logger->alert($message, $context);
        $this->addJobDetails($context, $message, Logger::ALERT);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = array())
    {
        $result = $this->logger->critical($message, $context);
        $this->addJobDetails($context, $message, Logger::CRITICAL);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = array())
    {
        $result = $this->logger->error($message, $context);
        $this->addJobDetails($context, $message, Logger::ERROR);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = array())
    {
        $result = $this->logger->warning($message, $context);
        $this->addJobDetails($context, $message, Logger::WARNING);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = array())
    {
        $result = $this->logger->notice($message, $context);
        $this->addJobDetails($context, $message, Logger::NOTICE);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = array())
    {
        $result = $this->logger->info($message, $context);
        $this->addJobDetails($context, $message, Logger::INFO);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = array())
    {
        $result = $this->logger->debug($message, $context);
        $this->addJobDetails($context, $message, Logger::DEBUG);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        $result = $this->logger->log($level, $message, $context);
        $level = Logger::toMonologLevel($level);
        $this->addJobDetails($context, $message, $level);

        return $result;
    }

    /**
     * Get the wrapped Monolog logger
     *
     * @return Logger
     */
    public function getMonologLogger()
    {
        return $this->logger;
    }

    /**
     * If the caller added a Job object to the context, log the job details too
     *
     * @param array  $context The log message context, maybe containing a Job
     * @param string $message The log message
     * @param string $level   The log level as defined in Monolog\Logger
     */
    protected function addJobDetails(array $context, $message, $level)
    {
        if ( ! empty($context[self::JOB_INDEX])
            and $context[self::JOB_INDEX] instanceof JobInterface
        ) {
            $job = $context[self::JOB_INDEX];

            $logJobEvent = new LogJobDetailsEvent($message, $level, $job, $context);
            $this->eventDispatcher->dispatch(Events::LOG_JOB_DETAILS, $logJobEvent);

            $jobBody = $this->serializer->serialize($job->getBody());
            $extraMessage = MessageFormatter::jobDetails($job->getId(), $jobBody, $job->getOriginalId());

            $this->logger->debug($extraMessage, $context);
        }
    }
}
