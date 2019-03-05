<?php
namespace KanbanBoard;


use Cache\Adapter\PHPArray\ArrayCachePool;
use Github\Client;

/**
 * Class GithubClient; mostly a wrapper around KnpLabs/php-github-api client.
 * @package KanbanBoard
 */
class GithubClient
{
    /**
     * The client to the GitHub API.
     * @see https://github.com/KnpLabs/php-github-api
     * @var Github\Client
     */
    private $client;

    /**
     * The GitHub account.
     * @var string
     */
    private $account;

    /**
     * GithubClient constructor.
     * @param string $token The token to access GitHub API.
     * @param string $account The account to access.
     */
    public function __construct(string $token, string $account)
    {
        $this->account = $account;
        $this->client= new Client();
        $this->client->addCache(new ArrayCachePool());
        $this->client->authenticate($token, null,Client::AUTH_HTTP_TOKEN);
    }

    /**
     * Returns the API milestones data of a repository as array.
     * @param string $repository The name of the repository.
     * @return array The API response as array.
     */
    public function milestones(string $repository) :array
    {
       return $this->client->api('issues')->milestones()->all($this->account, $repository);
    }

    /**
     * Returns the API issues data from a given milestone.
     * @param string $repository The name of the repository.
     * @param int $milestoneID The ID identifying the milestone in the array.
     * @return array
     */
    public function issues(string $repository, int $milestoneID) :array
    {
        $issue_parameters = array('milestone' => $milestoneID, 'state' => 'all');
        return $this->client->api('issue')->all($this->account, $repository, $issue_parameters);
    }

}