<?php


namespace KanbanBoard\DataObjects;

class Milestone
{
    public static $QUEUED = 'queued';
    public static $ACTIVE = 'active';
    public static $COMPLETED = 'completed';


    /**
     * Milestone number identifying a milestone in a given repository
     * @var int
    */
    public $number;

    /**
     * The title of the milestone.
     * @var string
     */
    public $title;

    /**
     * The URL to the milestone.
     * @var string
     */
    public $url;

    /**
     * The rapport between closed and open issue as a percent.
     * @var float
     */
    public $progress = NULL;

    /**
     * Array of queued issues (not completed issues without an assignee) for the milestone.
     * @var  array()
     * @see Issue
     */
    public $queued = array();

    /**
     * Array of active issues (not completed issues with an assignee) for the milestone.
     * @var array
     * @see Issue
     */
    public $active = array();

    /**
     * Array of completed issues.
     * @var array
     * @see Issue
     */
    public $completed = array();

    /**
     * Milestone constructor.
     * @param int $number The number identifying the milestone in the GitHub API.
     * @param string $title The title or name of the Milestone.
     * @param string $url The URL pointing to the Milestone in GitHub.
     * @param float $progress The % of closed issues.
     */
    public function __construct(int $number, string $title, string $url, float $progress)
    {
        $this->number = $number;
        $this->title = $title;
        $this->url = $url;
        $this->progress = $progress;
    }

    /**
     * Adds an issue to the completed, queued or active list, according to the issue's state.
     * @param Issue $issue The issue to add.
     * @see Issue::$ACTIVE
     * @see Issue::$COMPLETED
     * @see Issue::$QUEUED
     */
    public function addIssue(Issue $issue) {
        switch ($issue->state) {
            case Issue::$ACTIVE:
                $this->active[] = $issue;
                break;
            case Issue::$QUEUED:
                $this->queued[] = $issue;
                break;
            case Issue::$COMPLETED:
                $this->completed[] = $issue;
                break;
            default:
                error_log(sprintf("unknown issue state %s with issue %s in %s milestone", $issue->state, $issue->title, $this->title));
                break;
        }
    }

}