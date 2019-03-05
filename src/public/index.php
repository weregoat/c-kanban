<?php

use KanbanBoard\Authentication;
use KanbanBoard\GithubClient;
use KanbanBoard\Utilities;
use KanbanBoard\DataObjects\Repository;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $repositoryNames = explode('|', Utilities::env('GH_REPOSITORIES'));
    $pausingLabels = explode('|', Utilities::env('PAUSING_LABELS', ''));
    /* OAuth App authorisation is okay, but private token works too. YMMV */
    /* https://help.github.com/en/articles/creating-a-personal-access-token-for-the-command-line */
    $token = $token = Utilities::env('GH_TOKEN', '');
    if (empty($token)) {
        $authentication = new Authentication();
        $token = $authentication->getToken();
    }

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
    echo "<p>Server Error</p>";
}

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader('../views'),
));
echo $m->render('index', array('repositories' => $repositories));

