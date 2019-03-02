<?php


namespace KanbanBoard\DataObjects;



use KanbanBoard\Utilities;
use Michelf\Markdown;

class Issue
{

    public static $KEY_ID = 'id';
    public static $KEY_STATE = 'state';
    public static $KEY_TITLE = 'title';
    public static $KEY_ASSIGNEE = 'assignee';

    public static $COMPLETED = 'completed';
    public static $ACTIVE = 'active';
    public static $QUEUED = 'queued';

    public static $PAUSED = 1;
    public static $NOT_PAUSED = 0;

    public $ID;
    public $title;
    public $body;
    public $url;
    public $assignee = NULL;
    public $paused;
    public $progress;

    public $state;
    public $sourceData = array();

    public function __construct($issueData = array(), $pausingLabels = array())
    {
        $this->sourceData = $issueData;
        $this->title = $this->sourceData[self::$KEY_TITLE];
        $this->setState();
        $this->body = Markdown::defaultTransform($this->sourceData['body']);
        $this->url = $this->sourceData['html_url'];
        $this->setPaused($pausingLabels);

        /*
                'progress'			=> self::_percent(
        substr_count(strtolower($ii['body']), '[x]'),
        substr_count(strtolower($ii['body']), '[ ]')),
        */
    }


    /**
     * Sets the state of the issue according to issue state and assignee.
     * It also sets the assignee, if any.
     * @see self::COMPLETED
     * @see self::ACTIVE
     * @see self::QUEUED
     */
    private function setState() {
        $this->setAssignee();
        $state = Utilities::getValue($this->sourceData, self::$KEY_STATE);
        if ($state === 'closed') {
            $this->state = self::$COMPLETED;
        } else {
            if(!empty($this->assignee)) {
                $this->state = self::$ACTIVE;
            } else {
                $this->state = self::$QUEUED;
            }
        }
    }

    /**
     * Sets the assignee as the URL of the assignee, if present.
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
     * @uses self::PAUSED
     * @uses self::NOT_PAUSED
     */
    private function setPaused(array $pausingLabels) {
        $this->paused = self::$NOT_PAUSED;
        $labels = Utilities::getValue($this->sourceData, 'labels');
        if (! empty($labels) AND ! empty($pausingLabels)) {
            foreach ($labels as $label) {
                if (in_array($label['name'], $pausingLabels)) {
                    $this->paused = self::$PAUSED;
                    break;
                }
            }
        }
    }
}
