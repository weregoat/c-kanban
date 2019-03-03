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
     * Original array from the GitHub API client.
     * @var array
     */
    private $sourceData;

    /**
     * Issue constructor.
     * @param array $issueData The array from the GitHub API client.
     */
    public function __construct($issueData = array())
    {
        $this->sourceData = $issueData;
        $this->title = $this->sourceData[self::TITLE];
        $this->setState();
        $this->url = $this->sourceData[self::URL];
        $this->number = $this->sourceData[self::NUMBER];
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
     * @see self::COMPLETED
     * @see self::ACTIVE
     * @see self::QUEUED
     * @uses Utilities::getValue()
     */
    private function setState() {
        $this->setAssignee();
        $state = Utilities::getValue($this->sourceData, self::STATE);
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
     * @uses Utilities::getValue()
     */
    private function setAssignee() {
        $assignee = Utilities::getValue($this->sourceData, 'assignee');
        if (!empty($assignee)) {
            $url = Utilities::getValue($assignee, 'avatar_url');
            if (!empty($url)) {
                $this->assignee = $url.'?s=16';
            }
        }
    }

    /**
     * Sets the pause property for active issues only according to the presence or not of any label
     * with a name matching any pausing label.
     * @param array $pausingLabels The array with the label names that will make an issue paused
     * @param bool $caseSensitive If the comparision should be case sensitive.
     * @return bool The current paused state of the issue.
     */
    public function isPaused(array $pausingLabels, bool $caseSensitive = TRUE) :bool {
        if ($this->state == self::ACTIVE) {
            $labels = array();
            foreach(Utilities::getArrayValue($this->sourceData, 'labels') as $label) {
                $labels[] = $label['name'];
            }
            if (!empty($labels) AND !empty($pausingLabels)) {
                if (!$caseSensitive) {
                    array_walk($pausingLabels, function($label) {return strtolower($label);});
                    array_walk($labels, function($label) {return strtolower($label);});
                }
                $intersection = array_intersect($pausingLabels, $labels);
                if (!empty($intersection)) {
                    $this->paused = TRUE;
                }
            }
        }
        return $this->paused;
    }
}
