<?php


namespace KanbanBoard\DataObjects;


use Github\Exception\RuntimeException;
use KanbanBoard\GithubClient;

class Repository
{
    /**
     * Key for the name property when this repository exported as array.
     * @see self::toArray
     * @var string
     */
    const NAME = 'name';

    /**
     * Key for the name of the array of milestones when this repository is exported as array.
     * @see self::toArray
     * @var string
     */
    const MILESTONES = 'milestones';

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
     * Note that, as opposed to _fetchMilestones_ the milestone will not automatically fetch the associated issues.
     * @param Milestone $milestone The milestone to add.
     * @param string|NULL $key Optionally use the given string as array key instead of the title (or title + number).
     * @return bool False in case the key is duplicated and the milestone was not added.
     * @see self::FetchMilestones()
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
            /* Is not clear to me from the GitHub API if it's possible to have duplicated titles */
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
     * Fetch from GitHub the milestones with the associated issues from GitHub.
     * @param GithubClient $client The client for querying the GitHub API.
     * @param array|null $pausingLabels Optionally specify an array of labels that will mark the active issues as paused.
     */
    public function fetchMilestones(GithubClient $client, $pausingLabels = array())
    {
        try {
            $milestones = $client->milestones($this->name);
            foreach ($milestones as $data) {
                $closedIssues = $data['closed_issues'];
                $openIssues = $data['open_issues'];
                /* We skip milestones without issues (open or closed) */
                if ($openIssues != 0 AND $closedIssues != 0) {
                    $milestone = new Milestone(
                        $this,
                        $data['number'],
                        $data['title'],
                        $data['html_url']
                    );
                    $milestone->fetchIssues($client, $pausingLabels);
                    $this->addMilestone($milestone);
                }
            }
            $this->sortMilestones();
        } catch (RuntimeException $githubRuntimeException) { // Other kind of exception from the client may require different handling
            error_log(sprintf(
                "Could not retrieve milestones from repository %s because of %s with message: %s",
                $this->name,
                get_class($githubRuntimeException),
                $githubRuntimeException->getMessage()
            ));
        }
    }

    /**
     * Export the repository as an array.
     * @return array
     */
    public function toArray()
    {
        $repository = array();
        foreach($this->milestones as $milestone) {
            $repository[self::MILESTONES][] = $milestone->toArray();
        }
        $repository[self::NAME] = $this->name;
        return $repository;
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