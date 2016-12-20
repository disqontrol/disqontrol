<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Test\Integration;

use Disqontrol\Scheduler\CrontabParser;

class CrontabTest extends \PHPUnit_Framework_TestCase
{
    const EXAMPLE_CRONTAB = 'docs/examples/crontab';
    const ENTRY_COUNT = 3;
    const SECOND_QUEUE = 'rss-update';

    public function testExampleCrontabParsesCorrectly()
    {
        $crontab = $this->loadCrontab();
        $parser = new CrontabParser();
        $entries = $parser->parse($crontab);

        // Test that the parser skips the comments in the example crontab
        // and leaves only the well formatted rows
        $this->assertCount(self::ENTRY_COUNT, $entries);

        $secondQueue = $entries[1]->getJob()->getQueue();
        $this->assertEquals(self::SECOND_QUEUE, $secondQueue);
    }

    /**
     * @return string
     */
    private function loadCrontab()
    {
        $crontab = file_get_contents(self::EXAMPLE_CRONTAB);

        return $crontab;
    }
}
