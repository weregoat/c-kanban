<?php


namespace KanbanBoard\DataObjects;


class Repository
{
    public $name;
    public $milestones = array();

    public function __construct(string $name)
    {
        $this->name = trim($name);
    }

    public function addMilestone(Milestone $milestone) {
        $this->milestones[$milestone->title] = $milestone;
    }

    public function sortMilestones() {
        ksort($this->milestones);
    }


}