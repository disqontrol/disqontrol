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

use Monolog\Formatter\LineFormatter as DefaultLineFormatter;

/**
 * A log line formatter that omits the empty brackets at the line end
 *
 * @see https://stackoverflow.com/questions/13968967/how-not-to-show-last-bracket-in-a-monolog-log-line
 *
 * It also removes the Job object from the $context variable, because
 * we log it differently.
 *
 * @see Disqontrol\Logger\JobLogger::addJobDetails()
 *
 * @author Martin Schlemmer
 */
class LineFormatter extends DefaultLineFormatter
{
    const CONTEXT_KEY = 'context';

    /**
     * @param string $format                The format of the message
     * @param string $dateFormat            The format of the timestamp: one supported by DateTime::format
     * @param bool   $allowInlineLineBreaks Whether to allow inline line breaks in log entries
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false)
    {
        $ignoreEmptyContextAndExtra = true;
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * {@inheritdoc}
     *
     * Remove the job object from the context array
     */
    public function format(array $record)
    {
        if ( ! empty($record[self::CONTEXT_KEY][JobLogger::JOB_INDEX])) {
            unset($record[self::CONTEXT_KEY][JobLogger::JOB_INDEX]);
        }

        return parent::format($record);
    }
}
