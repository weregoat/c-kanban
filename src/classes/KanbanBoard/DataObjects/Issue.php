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
     * Value for paused issued (because of sorting method)
     * @var int
     */
    const PAUSED = 1;

    /**
     * Value for issues not paused (because of sorting method)
     * @var int
     */
    const NOT_PAUSED = 0;

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
     * The value marking a paused issue.
     * @see self::PAUSED
     * @see self::NOT_PAUSED
     * @var int
     */
    public $paused;

    /**
     * The state of the issue.
     * @see self::COMPLETED
     * @see self::QUEUED
     * @see self::ACTIVE
     * @var string
     */
    public $state;

    /**
     * Original array from the GitHub API client.
     * @var array
     */
    private $sourceData;

    /**
     * Issue constructor.
     * @param array $issueData The array from the GitHub API client.
     * @param array|NULL $pausingLabels Optionally a list of labels that will mark the issue as paused.
     */
    public function __construct($issueData = array(), $pausingLabels = array())
    {
        $this->sourceData = $issueData;
        $this->title = $this->sourceData[self::TITLE];
        $this->setState();
        $this->url = $this->sourceData[self::URL];
        $this->setPaused($pausingLabels);
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
     * Sets a value of 1 or 0 (because later sorting) as the paused property according
     * to the presence or not of any label with a name matching any pausing label.
     * @param array $pausingLabels The array with the label names that will make an issue paused
     * @see self::PAUSED
     * @see self::NOT_PAUSED
     */
    private function setPaused(array $pausingLabels) {
        $this->paused = self::NOT_PAUSED;
        $labels = Utilities::getValue($this->sourceData, 'labels');
        if (! empty($labels) AND ! empty($pausingLabels)) {
            foreach ($labels as $label) {
                if (in_array($label['name'], $pausingLabels)) {
                    $this->paused = self::PAUSED;
                    break;
                }
            }
        }
    }
}
