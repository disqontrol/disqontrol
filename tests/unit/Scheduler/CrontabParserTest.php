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



class CrontabParserTest extends \PHPUnit_Framework_TestCase
{
    const CRON_EXP = '* * * * *';
    const QUEUE = 'queue';
    const JOB_BODY = 1;
    /**
     * @var CrontabParser
     */
    private $cp;
    
    protected function setUp()
    {
        $this->cp = new CrontabParser();
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(CrontabParser::class, $this->cp);
    }

    public function testParseStandardRow()
    {
        $crontab = $this->createCronRow(self::CRON_EXP, self::QUEUE, self::JOB_BODY);

        $entries = $this->cp->parse($crontab);

        $entry = current($entries);
        $this->assertEquals(self::CRON_EXP, $entry->getCronExpression()->getExpression());
        $this->assertEquals(self::QUEUE, $entry->getJob()->getQueue());
        $this->assertEquals(self::JOB_BODY, $entry->getJob()->getBody());
    }

    public function testSkipMalformedCronExpression()
    {
        $cronExp1 = 'a b c * *';
        $crontab = $this->createCronRow($cronExp1, self::QUEUE, self::JOB_BODY) . "\n"
                 . $this->createCronRow(self::CRON_EXP, self::QUEUE, self::JOB_BODY);

        $entries = $this->cp->parse($crontab);

        $this->assertCount(1, $entries);

        $entry = current($entries);
        $this->assertEquals(self::CRON_EXP, $entry->getCronExpression()->getExpression());
    }

    public function testJobBodyWithWhiteSpace()
    {
        $body = 'job body   with white space';
        $crontab = $this->createCronRow(self::CRON_EXP, self::QUEUE, $body);

        $entries = $this->cp->parse($crontab);

        $entry = current($entries);
        $this->assertEquals($body, $entry->getJob()->getBody());
    }

    public function testMultipleEntries()
    {
        $entryData = [
            ['15 5 * * *', 'job-body'],
            ['02 4 7 * *', 'job with white-space'],
            ['malformed * cron expression *', self::JOB_BODY],
            [self::CRON_EXP, '1 2 3 4 5 job body']
        ];

        $crontab = '';
        foreach ($entryData as $row) {
            $crontab .= "\n" . $this->createCronRow($row[0], self::QUEUE, $row[1]);
        }

        $entries = $this->cp->parse($crontab);

        $this->assertCount(3, $entries);
        $this->assertEquals($entryData[0][0], $entries[0]->getCronExpression()->getExpression());
        $this->assertEquals($entryData[1][1], $entries[1]->getJob()->getBody());
        $this->assertEquals($entryData[3][1], $entries[2]->getJob()->getBody());
    }

    private function createCronRow($cronExp, $queue, $body)
    {
        return $cronExp . ' ' . $queue . ' ' . $body;
    }
}
