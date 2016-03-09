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

use Disqontrol\Logger\MessageFormatter as msg;

class MessageFormatterTest extends \PHPUnit_Framework_TestCase
{
    const JOB_ID = 'job_id';
    const ORIGINAL_ID = 'original_id';
    const QUEUE = 'queue';

    public function testJustOneId()
    {
        $message = msg::jobDetails(self::JOB_ID, 'foo');
        $this->assertContains(self::JOB_ID, $message);
        $this->assertNotContains(self::ORIGINAL_ID, $message);
    }

    public function testBothIds()
    {
        $message = msg::jobDetails(self::JOB_ID, 'foo', self::ORIGINAL_ID);
        $this->assertContains(self::JOB_ID, $message);
        $this->assertContains(self::ORIGINAL_ID, $message);
    }

    public function testTwoSameIds()
    {
        $message = msg::jobDetails(self::JOB_ID, 'foo', self::JOB_ID);
        $this->assertContains(self::JOB_ID, $message);
        $this->assertNotContains(self::ORIGINAL_ID, $message);
        $this->assertTrue(substr_count($message, self::JOB_ID) === 1);
    }
}
