<?php


namespace KanbanBoard\DataObjects;


use KanbanBoard\GithubClient;

class Repository
{
    /**
     * The name of the repository as identified in the API.
     * @var string
     */
    public $name;

    /**
     * The list of milestones (with issues) in the repository.
     * @var array
     */
    public $milestones = array();

    /**
     * Repository constructor.
     * @param string $name The name of the repository.
     */
    public function __construct(string $name)
    {
        $this->name = trim($name);
    }

    /**
     * Add a milestone to the collection of milestones associated with the repository.
     * @param Milestone $milestone The milestone to add.
     * @param string|NULL $key Optionally use the given string as array key instead of the title (or title + number).
     * @return bool False in case the key is duplicated and the milestone was not added.
     */
    public function addMilestone(Milestone $milestone, string $key = NULL): bool
    {
        /*
        I am using the title as key like the original code (kind of) as
        it is used for sorting the milestones, but possibly the number
        would be a saner choice.
        */
        if (empty($key)) {
            $key = $milestone->title;
        }
        if (!array_key_exists($key, $this->milestones)) {
            $this->milestones[$key] = $milestone;
        } else {
            /*
              So, on my initiative, I am going to change the key if there are
              duplicates. GitHub API is not clear if it's possible to have duplicated titles
            */
            $oldMilestone = $this->milestones[$key];
            $altKey = $this->getExtendedKey($oldMilestone->title, $oldMilestone->number);
            /* Check for duplicates. It should not happen as the number is unique */
            if (!array_key_exists($altKey, $this->milestones)) {
                unset($this->milestones[$key]);
                $this->addMilestone($oldMilestone, $altKey);
                $this->addMilestone($milestone, $this->getExtendedKey($milestone->title, $milestone->number));
            } else {
                error_log("cannot add milestone %d(%s) as another with the same title and number already exists");
                return false;
            }
        }
        return true;
    }

    /**
     * Sorts the milestones according to the title.
     */
    public function sortMilestones()
    {
        ksort($this->milestones);
    }

    /**
     * Fetch from GitHub the milestones with issues from GitHub.
     * @param GithubClient $client The client for querying the GitHub API.
     */
    public function fetchMilestones(GithubClient $client)
    {
        foreach ($client->milestones($this->name) as $data) {
            $closedIssues = $data['closed_issues'];
            $openIssues = $data['open_issues'];
            /* We skip milestones without issues (open or closed) */
            if ($openIssues != 0 AND $closedIssues != 0) {
                $milestone = new Milestone(
                    $this,
                    $data['number'],
                    $data['title'],
                    $data['html_url'],
                    $this->percent($closedIssues, $openIssues)
                );
                $this->addMilestone($milestone);
            }
        }
        $this->sortMilestones();
    }

    /**
     * Returns a percent of closed issues.
     * @param int $closed The number of closed issues.
     * @param int $open The number of open issues.
     * @return float
     */
    private function percent(int $closed, int $open): float
    {
        $percent = 0.0;
        $total = $closed + $open;
        if ($total > 0) {
            $percent = round($closed / $total * 100);
        }
        return $percent;
    }

    /**
     * Simple wrapper to get alternative key for milestones
     * @param string $title The title of the milestone
     * @param int $number The number of the milestone
     * @return string "[title] #[number]"
     */
    private function getExtendedKey(string $title, int $number): string
    {
        return sprintf("%s #%d", $title, $number);
    }


}