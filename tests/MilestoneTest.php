<?php
/**
 * Created by IntelliJ IDEA.
 * User: reroc
 * Date: 3/7/19
 * Time: 2:03 PM
 */

use KanbanBoard\DataObjects\Milestone;
use KanbanBoard\DataObjects\Repository;
use KanbanBoard\DataObjects\Issue;
use PHPUnit\Framework\TestCase;

class MilestoneTest extends TestCase
{

    const NUMBER = 12345;
    const TITLE = 'a title';
    const URL = 'some url';
    const REPO_NAME = 'a name';

    public function testToArray()
    {
        $milestone = $this->newMilestone(true);
        $array = $milestone->toArray();
         $this->assertEquals(self::NUMBER, $array[Milestone::NUMBER]);
        $this->assertEquals(self::TITLE, $array[Milestone::TITLE]);
        $this->assertEquals(self::URL, $array[Milestone::URL]);
        $this->assertEquals(IssueTest::ASSIGNEE, $array[Milestone::ISSUES][Issue::COMPLETED][0][Issue::ASSIGNEE]);
    }


    public function testCalculateProgress()
    {
        $milestone = $this->newMilestone(true);
        $milestone->calculateProgress();
        $this->assertEquals(100.0, $milestone->progress);
        return $milestone;
    }


    public function testConstruct()
    {
        $milestone = $this->newMilestone(false);
        $this->assertEquals(self::REPO_NAME, $milestone->repository->name);
        $this->assertEquals(self::NUMBER, $milestone->number);
        $this->assertEquals(self::TITLE, $milestone->title);
        $this->assertEquals(self::URL, $milestone->url);
        $this->assertTrue(empty($milestone->getIssues(Issue::ACTIVE)));
        $this->assertTrue(empty($milestone->getIssues(Issue::QUEUED)));
        $this->assertTrue(empty($milestone->getIssues(Issue::COMPLETED)));
        $this->assertEquals(0.0, $milestone->progress);
    }

    public function testAddIssue()
    {
        $milestone = $this->newMilestone(true);
        $this->assertTrue(empty($milestone->getIssues(Issue::ACTIVE)));
        $this->assertTrue(empty($milestone->getIssues(Issue::QUEUED)));
        $completed = $milestone->getIssues(Issue::COMPLETED);
        $this->assertFalse(empty($completed));
        $this->assertEquals(IssueTest::ASSIGNEE, $completed[0]->assignee);
        return $milestone;
    }

    private function newMilestone($withIssue = false)
    {

        $repository = new Repository(self::REPO_NAME);
        $milestone = new Milestone($repository, self::NUMBER, self::TITLE, self::URL);
        if ($withIssue == true) {
            $data = [
                'state' => 'closed',
                Issue::URL => IssueTest::URL,
                Issue::TITLE => IssueTest::TITLE,
                Issue::NUMBER => IssueTest::NUMBER,
                Issue::ASSIGNEE => ['avatar_url' => IssueTest::ASSIGNEE],
                'labels' => [
                    ['name' => 'label1'],
                    ['name' => 'label2']
                ]
            ];
            $issue = new Issue($data);
            $milestone->addIssue($issue);
        }
        return $milestone;
    }
}
