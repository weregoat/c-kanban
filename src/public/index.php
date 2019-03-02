<?php
use KanbanBoard\Authentication;
use KanbanBoard\GithubClient;
use KanbanBoard\Utilities;
use KanbanBoard\Application;

require_once __DIR__.'/../../vendor/autoload.php';

$repositories = explode('|', Utilities::env('GH_REPOSITORIES'));
$authentication = new Authentication();
$token = $authentication->login();
$github = new GithubClient($token, Utilities::env('GH_ACCOUNT'));
$board = new Application($github, $repositories, array('waiting-for-feedback', 'API', 'php-cache', 'Performance'));
$data = $board->board();
/* https://github.com/bobthecow/mustache.php/wiki */
/* https://github.com/bobthecow/mustache.php/wiki/Variable-Resolution */

$m = new Mustache_Engine(array(
	'loader' => new Mustache_Loader_FilesystemLoader('../views'),
));
echo $m->render('index', array('milestones' => $data));
