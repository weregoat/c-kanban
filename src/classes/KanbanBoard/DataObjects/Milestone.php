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
     */
    public function __construct(Repository $repository, int $number, string $title, string $url)
    {
        $this->repository = $repository;
        $this->number = $number;
        $this->title = $title;
        $this->url = $url;
        $this->issues = array(
            Issue::QUEUED => [],
            Issue::ACTIVE => [],
            Issue::COMPLETED => [],
        );
    }

    /**
     * Adds an issue to the completed, queued or active list, according to the issue's state.
     * @param Issue $issue The issue to add.
     * @param bool|NULL $updateProgress Defaults to TRUE; Sets to FALSE to avoid updating the progress percentage right after the addition.
     * @see Issue::ACTIVE
     * @see Issue::COMPLETED
     * @see Issue::QUEUED
     * @uses self::calculateProgress()
     */
    public function addIssue(Issue $issue, bool $updateProgress = TRUE)
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
        if ($updateProgress) {
            $this->calculateProgress();
        }
    }

    /**
     * Returns the issues from the Milestone as an array.
     * @param string|NULL $state Optionally only returns an array of the issues with the given state.
     * @return array
     * @see ISSUE::ACTIVE
     * @see ISSUE::COMPLETED
     * @see ISSUE::QUEUED
     */
    public function getIssues(string $state = ""): array
    {
        $issues = $this->issues;
        $state = trim(strtolower($state));
        if (!empty($state)) {
            if (array_key_exists($state, $issues)) {
                $issues = $issues[$state];
            } else {
                $issues = array();
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
     * Sort the active issues according to the following method:
     * - Un-paused first
     * - Paused
     * Paused and un-paused are then sorted by issue number.
     */
    public function sortActiveIssues()
    {
        $active = $this->issues[Issue::ACTIVE];
        usort($active, function ($a, $b) {
            /*
             * https://secure.php.net/manual/en/function.usort.php
             * The comparison function must return an integer less than, equal to,
             * or greater than zero if the first argument is considered to be respectively
             * less than, equal to, or greater than the second
             */
            if ($a->paused === $b->paused) {
                if ($a->number < $b->number) {
                    return -1;
                } else {
                    return 1;
                }
                return 0; // Same number?
            } else {
                if ($a->paused) {
                    return 1;
                }
                return -1;
            }
            /*
             * Original code (using an array for paused); notice that the paused array would contain only one or 0 elements:
            return count($a['paused']) - count($b['paused']) === 0 ? strcmp($a['title'], $b['title']) : count($a['paused']) - count($b['paused']);
            */
        });
        $this->issues[Issue::ACTIVE] = $active;
    }

    /**
     * Fetch the issues associated with this milestone from GitHub.
     * @param GithubClient $client The client to the GitHub's API.
     * @param array|null $pausingLabels Optionally use the given list of labels to mark active issues as paused.
     * @uses self::sortActiveIssues()
     * @uses self::calculateProgress()
     * @uses self::addIssue
     * @uses GithubClient::issues()
     */
    public function fetchIssues(GithubClient $client, $pausingLabels = array())
    {
        $issues = $client->issues($this->repository->name, $this->number);
        /* https://developer.github.com/v3/issues/#list-issues */
        /* https://developer.github.com/v3/issues/#response-1 */
        foreach ($issues as $data) {
            if (isset($data['pull_request']))
                continue;
            $issue = new Issue($data);
            /* Only active issues can be paused */
            if ($issue->state == Issue::ACTIVE) {
                $issue->isPaused($pausingLabels, false);
            }
            $this->addIssue($issue, false);
        }
        $this->sortActiveIssues();
        $this->calculateProgress();
    }

    /**
     * Calculate the rapport between active and non active issues as a percentage.
     * @uses self::getIssues
     */
    public function calculateProgress() {
        /*
         * Because the GitHub API considers pull-requests as equivalent to
         * issues, the original method to calculate the progress of a milestone
         * by using 'closed_issue' and 'open_issue' values from the API does
         * not really reflect what you will see in the Kanban board.
         * I took the liberty to refactor the calculation using only the issues
         * that will show on the board (active + queue) as open, (completed) as
         * closed.
         */
        $active = count($this->getIssues(Issue::ACTIVE));
        $queued = count($this->getIssues(Issue::QUEUED));
        $open = $active + $queued;
        $closed = count($this->getIssues(Issue::COMPLETED));
        $total = $closed + $open;
        if ($total > 0) {
            $this->progress = round($closed / $total * 100);
        }

    }
}