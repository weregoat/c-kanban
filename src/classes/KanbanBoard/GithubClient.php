<?php
namespace KanbanBoard;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class GithubClient
{
    private $client;
    private $milestone_api;
    private $account;

    public function __construct($token, $account)
    {
        $this->account = $account;
        $this->client= new \Github\Client();
        $adapter = new Local('/tmp/github-api-cache/');
        $fileSystem = new Filesystem($adapter);
        $this->client->addCache(new FilesystemCachePool($fileSystem));
        $this->client->authenticate($token, \Github\Client::AUTH_HTTP_TOKEN);
        $this->milestone_api = $this->client->api('issues')->milestones();
    }

    public function milestones($repository)
    {
        return $this->milestone_api->all($this->account, $repository);
    }

    public function issues($repository, $milestone_id)
    {
        $issue_parameters = array('milestone' => $milestone_id, 'state' => 'all');
        return $this->client->api('issue')->all($this->account, $repository, $issue_parameters);
    }

    public function __destruct()
    {
        $this->client->removeCache();
    }
}