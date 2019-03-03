<?php
namespace KanbanBoard;


use Cache\Adapter\PHPArray\ArrayCachePool;
use Github\Client;

class GithubClient
{
    private $client;
    private $milestone_api;
    private $account;

    public function __construct($token, $account)
    {
        $this->account = $account;
        $this->client= new Client();
        $this->client->addCache(new ArrayCachePool());
        $this->client->authenticate($token, Client::AUTH_HTTP_TOKEN);
        $this->milestone_api = $this->client->api('issues')->milestones();
    }

    public function milestones($repository) :array
    {
       return $this->milestone_api->all($this->account, $repository);
    }

    public function issues($repository, $milestone_id)
    {
        $issue_parameters = array('milestone' => $milestone_id, 'state' => 'all');
        return $this->client->api('issue')->all($this->account, $repository, $issue_parameters);
    }

}