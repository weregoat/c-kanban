<?php
namespace KanbanBoard;

use Github\Client;
use KanbanBoard\DataObjects\Issue;
use KanbanBoard\DataObjects\Milestone;
use vierbergenlars\SemVer\version;

use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;
use \Michelf\Markdown;

class Application {

	public function __construct($github, $repositories, $paused_labels = array())
	{
		$this->github = $github;
		$this->repositories = $repositories;
		$this->paused_labels = $paused_labels;
	}

	public function board()
	{
	    $milestones = array();
		$ms = array();
		foreach ($this->repositories as $repository)
		{
			foreach ($this->github->milestones($repository) as $data)
			{
			    /* This is completely fucked up; if there are two milestones with the same title, but
			    in two different repositories, one will be overwritten. It makes no sense */
				$ms[$data['title']] = $data;
				$ms[$data['title']]['repository'] = $repository;
			}
		}
		ksort($ms);
		foreach ($ms as $name => $data)
		{
		    $closedIssues = $data['closed_issues'];
		    $openIssues = $data['open_issues'];
		    /* We skip milestones without issues (open or closed) */
		    if ($openIssues != 0 AND $closedIssues != 0) {
			    $issues = $this->issues($data['repository'], $data['number']);
			    $milestones[] = array(
					'milestone' => $name,
					'url' => $data['html_url'],
					'progress' => self::percent($closedIssues, $openIssues),
					'queued' => array_key_exists('queued', $issues) ? $issues['queued'] : [],
					'active' => array_key_exists('active', $issues) ? $issues['active'] : [],
					'completed' => array_key_exists('completed', $issues) ? $issues['completed'] : [],
				);
			}
		}
		return $milestones;
	}

	private function issues($repository, $milestone_id)
	{
	    $issues = array();
		$i = $this->github->issues($repository, $milestone_id);
		/* https://developer.github.com/v3/issues/#list-issues */
        /* https://developer.github.com/v3/issues/#response-1 */
		foreach ($i as $ii)
		{
			if (isset($ii['pull_request']))
				continue;
			$issue = new Issue($ii);
			/*
			$issues[$ii['state'] === 'closed' ? 'completed' : (($ii['assignee']) ? 'active' : 'queued')][] = array(
				'id' => $ii['id'], 'number' => $ii['number'],
				'title'            	=> $ii['title'],
				'body'             	=> Markdown::defaultTransform($ii['body']),
     'url' => $ii['html_url'],
				'assignee'         	=> (is_array($ii) && array_key_exists('assignee', $ii) && !empty($ii['assignee'])) ? $ii['assignee']['avatar_url'].'?s=16' : NULL,
				'paused'			=> self::labels_match($ii, $this->paused_labels),
				'progress'			=> self::_percent(
											substr_count(strtolower($ii['body']), '[x]'),
											substr_count(strtolower($ii['body']), '[ ]')),
				'closed'			=> $ii['closed_at']
			);
			*/
            $issues[$issue->state][] = array(
                //'id' => $issue->ID,
                //'number' => $ii['number'],
                'title'            	=> $issue->title,
                //'body'             	=> Markdown::defaultTransform($ii['body']),
                'url' => $ii['html_url'],
                'assignee'         	=> (is_array($ii) && array_key_exists('assignee', $ii) && !empty($ii['assignee'])) ? $ii['assignee']['avatar_url'].'?s=16' : NULL,
                'paused'			=> self::labels_match($ii, $this->paused_labels), // This is either an empty array or with one element
                //'progress'			=> self::_percent(
                //    substr_count(strtolower($ii['body']), '[x]'),
                //    substr_count(strtolower($ii['body']), '[ ]')),
                //'closed'			=> $ii['closed_at']
            );
		}
		if (array_key_exists('active', $issues)){
            /*
            Sorts issues in the active section by
            - title length ?!? if their pause status is the same
            - the non paused first
            But why?
            */
            usort($issues['active'], function ($a, $b) {
                if ($a['paused'] == $b['paused']) {
                    return strcmp($a['title'], $b['title']);
                } else {
                    if ($a['paused'] === TRUE) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
                /*
                 * Original code (using an array for paused):
                return count($a['paused']) - count($b['paused']) === 0 ? strcmp($a['title'], $b['title']) : count($a['paused']) - count($b['paused']);
                */
            });
        }
		return $issues;
	}

	private static function _state($issue)
	{
		if ($issue['state'] === 'closed')
			return 'completed';
		else if (Utilities::hasValue($issue, 'assignee') && count($issue['assignee']) > 0)
			return 'active';
		else
			return 'queued';
	}

	private static function labels_match($issue, $needles)
	{
		if(Utilities::hasValue($issue, 'labels')) {
			foreach ($issue['labels'] as $label) {
				if (in_array($label['name'], $needles)) {
					//return array($label['name']);
                    return TRUE;
				}
			}
		}
		return FALSE;

	}

	private static function percent($closed, $open) :float
	{
	    $percent = 0.0;
		$total = $closed + $open;
		if($total > 0)
		{
			$percent = round($closed / $total * 100);
		}
		return $percent;
	}

}
