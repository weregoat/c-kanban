<?php
/**
 * Created by IntelliJ IDEA.
 * User: reroc
 * Date: 3/7/19
 * Time: 1:31 PM
 */

use KanbanBoard\DataObjects\Issue;
use PHPUnit\Framework\TestCase;

class IssueTest extends TestCase
{
    const URL = 'a url';
    const PAUSED = true;
    const NOT_PAUSED = false;
    const TITLE = 'a title';
    const ASSIGNEE = 'some avatar';
    const NUMBER = 12345;
    const LABELS = ['label1', 'label2'];

    public function testConstructor() {
        $data = [
            'state' => 'closed',
            Issue::URL => self::URL,
            Issue::TITLE => self::TITLE,
            Issue::NUMBER => self::NUMBER,
            Issue::ASSIGNEE => ['avatar_url' => self::ASSIGNEE],
            'labels' => [
                ['name' => 'label1'],
                ['name' => 'label2']
            ]
        ];
        $issue = new Issue($data);
        $this->assertEquals(self::URL, $issue->url);
        $this->assertEquals(self::NOT_PAUSED, $issue->paused);
        $this->assertEquals(self::TITLE, $issue->title);
        $this->assertEquals(self::NUMBER, $issue->number);
        $this->assertEquals(self::ASSIGNEE, $issue->assignee);
        $this->assertEquals(self::LABELS, $issue->labels);

        return $issue;
    }

    /**
     * @depends testConstructor
     */
    public function testToArray(Issue $issue)
    {
        $array = $issue->toArray();
        $this->assertEquals(self::URL, $array[Issue::URL]);
        $this->assertEquals(self::ASSIGNEE, $array[Issue::ASSIGNEE]);
        $this->assertEquals(self::NOT_PAUSED, $array[Issue::PAUSED]);
        $this->assertEquals(self::TITLE, $array[Issue::TITLE]);
        return $issue;
    }

    /**
     * @param Issue $issue
     * @depends testToArray
     */
    public function testIsPaused(Issue $issue) {
        $issue->isPaused(['label1']);
        $this->assertEquals(self::NOT_PAUSED, $issue->paused);
        $issue->state = Issue::ACTIVE;
        $issue->isPaused(['label1']);
        $this->assertEquals(self::PAUSED, $issue->paused);
    }

}
