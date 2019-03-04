<?php


namespace KanbanBoard\DataObjects;



use KanbanBoard\Utilities;


/**
 * Class Issue
 * @package KanbanBoard\DataObjects
 */
class Issue
{


    /**
     * Key value for issue URL
     * @var string
     */
    const URL = 'html_url';

    /**
     * Key value for issue state
     * @var string
     */
    const STATE = 'state';

    /**
     * Key value for issue title
     * @var string
     */
    const TITLE = 'title';

    /**
     * Key value for assignee icon
     * @var string
     */
    const ASSIGNEE = 'assignee';

    /**
     * Key value for issue number.
     * @var string
     */
    const NUMBER = 'number';

    /**
     * Key value for issue paused flag.
     * @var string
     */
    const PAUSED = 'paused';

    /**
     * Completed status
     * @var string
     */
    const COMPLETED = 'completed';

    /**
     * Active (not completed with an assignee) status
     * @var string
     */
    const ACTIVE = 'active';

    /**
     * Queued (not completed and without assignee) status
     * @var string
     */
    const QUEUED = 'queued';

    /**
     * The title for the issue.
     * @var string
     */
    public $title;

    /**
     * The URL to the issue on GitHub.
     * @var string
     */
    public $url;

    /**
     * The URL to the avatar of the assignee.
     * @var string
     */
    public $assignee = NULL;

    /**
     * If the issue is paused. Must be active and have at least one of
     * specified labels.
     * @var bool
     */
    public $paused = FALSE;

    /**
     * The state of the issue.
     * @see self::COMPLETED
     * @see self::QUEUED
     * @see self::ACTIVE
     * @var string
     */
    public $state;

    /**
     * The number identifying the issue in the GitHub repository.
     * @var int
     */
    public $number;

    /**
     * Array with the name of the labels attached to the milestone.
     * @var array
     */
    public $labels = array();

    /**
     * Issue constructor.
     * @param array $issueAPIData The array with the data from the GitHub API client.
     */
    public function __construct(array $issueAPIData = array())
    {
        $this->title = Utilities::getValue($issueAPIData, self::TITLE);
        $this->setState($issueAPIData);
        $this->url = Utilities::getValue($issueAPIData, self::URL);
        $this->number = Utilities::getValue($issueAPIData, self::NUMBER);
        $this->setLabels($issueAPIData);
    }


    /**
     * Returns the issue as an array.
     * @return array
     * @see self::URL
     * @see self::TITLE
     * @see self::ASSIGNEE
     */
    public function toArray()
    {
        $array = array();
        $array[self::URL] = $this->url;
        $array[self::TITLE] = $this->title;
        $array[self::ASSIGNEE] = $this->assignee;
        $array[self::PAUSED] = $this->paused;
        return $array;
    }


    /**
     * Sets the state of the issue according to issue state and assignee.
     * It also sets the assignee, if any.
     * @param array $data The issue's data from the Github API client.
     * @see self::COMPLETED
     * @see self::ACTIVE
     * @see self::QUEUED
     * @uses Utilities::getValue()
     * @uses self::setAssignee
     */
    private function setState(array $data) {
        $this->setAssignee($data);
        $state = Utilities::getValue($data, self::STATE);
        if ($state === 'closed') {
            $this->state = self::COMPLETED;
        } else {
            if(!empty($this->assignee)) {
                $this->state = self::ACTIVE;
            } else {
                $this->state = self::QUEUED;
            }
        }
    }

    /**
     * Sets the assignee as the URL of the assignee, if present.
     * @param array $data The issue's data from the Github API client.
     * @uses Utilities::getValue()
     */
    private function setAssignee(array $data) {
        $assignee = Utilities::getValue($data, 'assignee');
        if (!empty($assignee)) {
            $url = Utilities::getValue($assignee, 'avatar_url');
            if (!empty($url)) {
                $this->assignee = $url;
            }
        }
    }

    /**
     * Sets the label names (lowercase) associated with the issue.
     * @param array $data The issue's data from the Github API client.
     */
    private function setLabels(array $data) {
        foreach(Utilities::getArrayValue($data, 'labels') as $label) {
            $this->labels[] = strtolower(trim($label['name']));
        }
    }

    /**
     * Sets the pause property for active issues only according to the presence or not of any label
     * with a name matching (case insensitive) any from the given list.
     * @param array $pausingLabels The array with the label names that will make an issue paused.
     * @return bool The current paused state of the issue.
     */
    public function isPaused(array $pausingLabels) :bool {
        if ($this->state == self::ACTIVE) {
            if (!empty($this->labels) AND !empty($pausingLabels)) {
                array_walk($pausingLabels, function($label) {return trim(strtolower($label));});
                $intersection = array_intersect($pausingLabels, $this->labels);
                if (!empty($intersection)) {
                    $this->paused = TRUE;
                }
            }
        }
        return $this->paused;
    }
}
