<?php

use KanbanBoard\Authentication;
use KanbanBoard\GithubClient;
use KanbanBoard\Utilities;
use KanbanBoard\DataObjects\Repository;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $authentication = NULL;
    $repositoryNames = explode('|', Utilities::env('GH_REPOSITORIES'));
    $pausingLabels = explode('|', Utilities::env('PAUSING_LABELS', ''));
    $clientID = Utilities::env('GH_CLIENT_ID');
    $clientSecret = Utilities::env('GH_CLIENT_SECRET');
    $scope = Utilities::env('GH_SCOPE', 'repo');
    $authentication = new Authentication($clientID, $clientSecret, $scope);
    $token = $authentication->getToken();
    $github = new GithubClient($token, Utilities::env('GH_ACCOUNT'));
    /* I find the original handling of multiple repositories absurd */
    /* So I am going to list the milestones by repository */
    $repositories = array();
    foreach ($repositoryNames as $name) {
        $repository = new Repository($name);
        $repository->fetchMilestones($github, $pausingLabels);
        $repositories[] = $repository->toArray();
    }
    /* https://github.com/bobthecow/mustache.php/wiki */
    /* https://github.com/bobthecow/mustache.php/wiki/Variable-Resolution */
} catch (\Exception $exception) {
    error_log($exception->getMessage());
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    printf("<p>Failed to retrieve milestones: %s</p>", $exception->getMessage());
    if ($authentication !== null) {
        $authentication->clearSession();
    }
    exit();
}

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader('../views'),
));
echo $m->render('index', array('repositories' => $repositories));

