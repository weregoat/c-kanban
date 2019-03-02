<?php


namespace KanbanBoard\DataObjects;

use KanbanBoard\GithubClient;

class Milestone
{

    /**
     * The key for the Milestone number.
     * @var string
     */
    const NUMBER = 'number';

    /**
     * The key for the milestone URL on GitHub.
     * @var string
     */
    const URL = 'url';

    /**
     * The key for the issues associated with the milestone;
     * @var string
     */
    const ISSUES = 'issues';

    /**
     * The key for the title of the milestone.
     * @var string
     */
    const TITLE = 'title';

    /**
     * The repository the milestone belongs to.
     * @var Repository
     */
    public $repository;

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
    public $progress = 0.0;


    /**
     * List of issues associated with this milestone.
     * @var array
     */
    private $issues;


    /**
     * Milestone constructor.
     * @param Repository $repository The repository the milestone belongs to.
     * @param int $number The number identifying the milestone in the GitHub API.
     * @param string $title The title or name of the Milestone.
     * @param string $url The URL pointing to the Milestone in GitHub.
     * @param float $progress The % of closed issues.
     */
    public function __construct(Repository $repository, int $number, string $title, string $url, float $progress = 0.0)
    {
        $this->repository = $repository;
        $this->number = $number;
        $this->title = $title;
        $this->url = $url;
        $this->progress = $progress;
        $this->issues = array(
            Issue::QUEUED => [],
            Issue::ACTIVE => [],
            Issue::COMPLETED => [],
        );
    }

    /**
     * Adds an issue to the completed, queued or active list, according to the issue's state.
     * @param Issue $issue The issue to add.
     * @see Issue::ACTIVE
     * @see Issue::COMPLETED
     * @see Issue::QUEUED
     */
    public function addIssue(Issue $issue)
    {
        $state = $issue->state;
        switch ($state) {
            case Issue::ACTIVE:
            case Issue::QUEUED:
            case Issue::COMPLETED:
                $this->issues[$state][] = $issue;
                break;
            default:
                error_log(sprintf("unknown issue state %s with issue %s in %s milestone", $issue->state, $issue->title, $this->title));
                break;
        }
    }

    /**
     * Returns the issues from the Milestone as an array.
     * @param string|NULL $state Optionally removes the issues that are not in the given state.
     * @return array Always including completed, queued, and active sub-arrays (although they may be empty).
     * @see ISSUE::ACTIVE
     * @see ISSUE::COMPLETED
     * @see ISSUE::QUEUED
     */
    public function getIssues(string $state = ""): array
    {
        $issues = $this->issues;
        $state = trim(strtolower($state));
        if (!empty($state)) {
            foreach ($issues as $key => $list) {
                if ($key !== $state) {
                    $issues[$key] = [];
                }
            }
        }
        return $issues;
    }

    /**
     * Returns the milestone as an array.
     * @return array
     * @see self::TITLE
     * @see self::URL
     * @see self::NUMBER
     * @see self::ISSUES
     */
    public function toArray(): array
    {
        $milestone = array();
        $milestone[self::TITLE] = $this->title;
        $milestone[self::URL] = $this->url;
        $milestone[self::NUMBER] = $this->number;
        $milestone[self::ISSUES] = $this->getIssues();
        return $milestone;
    }

    /**
     * Sort the active issues according to the original method:
     *   - title length ?!? if their pause property is the same
     *   - the paused property
     */
    public function sortActiveIssues()
    {
        /*
        Sorts issues in the active section by
        - title length ?!? if their pause status is the same
        - the non paused first
        But why?
        */
        $active = $this->issues[Issue::ACTIVE];
        usort($active, function ($a, $b) {
            if ($a->paused == $b->paused) {
                return strcmp($a->title, $b->title);
            } else {
                return $a->paused;
            }
            /*
             * Original code (using an array for paused):
            return count($a['paused']) - count($b['paused']) === 0 ? strcmp($a['title'], $b['title']) : count($a['paused']) - count($b['paused']);
            */
        });
        $this->issues[Issue::ACTIVE] = $active;
    }

    /**
     * Fetch the issues associated with this milestone from GitHub.
     * @param GithubClient $client The client to the GitHub's API.
     * @param array|null $pausedLabels Optionally use the given list of labels to mark active issues as paused.
     */
    public function fetchIssues(GithubClient $client, array $pausedLabels = array())
    {
        $issues = $client->issues($this->repository->name, $this->number);
        /* https://developer.github.com/v3/issues/#list-issues */
        /* https://developer.github.com/v3/issues/#response-1 */
        foreach ($issues as $data) {
            if (isset($data['pull_request']))
                continue;
            $issue = new Issue($data, $pausedLabels);
            $this->addIssue($issue);
        }
        $this->sortActiveIssues();
    }
}