<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Scheduler;

use Cron\CronExpression;
use Disqontrol\Job\Job;
use Exception;

/**
 * Parse a content of a crontab into CrontabEntry objects
 *
 * Disqontrol crontab rows have the following syntax:
 *
 * * * * * * queue job body
 *
 * That is
 * - five cron parts (we don't support the optional sixth part, year)
 * - a queue name
 * - a job body
 * All separated by a single white space
 *
 * The job body itself can contain white space.
 *
 * @author Martin Schlemmer
 */
class CrontabParser
{
    const REGEX_CRON_EXP_INDEX = 1;
    const REGEX_QUEUE_INDEX = 2;
    const REGEX_JOB_BODY_INDEX = 3;

    /**
     * Parse a crontab
     *
     * @param string $crontab
     *
     * @return CrontabEntry[]
     */
    public function parse($crontab)
    {
        $entries = array();

        $noLimit = -1;
        $rows = preg_split('/\r|\n|\r\n/', $crontab, $noLimit, PREG_SPLIT_NO_EMPTY);

        foreach ($rows as $row) {
            $entry = $this->parseRow($row);
            if ( ! empty($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Parse a single crontab row
     *
     * @param string $row
     *
     * @return CrontabEntry
     */
    private function parseRow($row)
    {
        $cronRegex = '(.+\s.+\s.+\s.+\s.+)\s';
        $jobRegex = '(.+)\s(.+)$';

        $rowRegex = '~' . $cronRegex . $jobRegex . '~U';

        preg_match($rowRegex, $row, $parts);

        try {
            $cronExpression = CronExpression::factory($parts[self::REGEX_CRON_EXP_INDEX]);
        } catch (Exception $e) {
            return false;
        }

        // Note: We could also use the JobFactory, but what we need here is just
        // a simple job without any metadata. It will be sent through Producer
        // which adds everything we need anyway.
        $job = new Job(
            $parts[self::REGEX_JOB_BODY_INDEX],
            $parts[self::REGEX_QUEUE_INDEX]
        );

        $entry = new CrontabEntry($cronExpression, $job);

        return $entry;
    }
}
