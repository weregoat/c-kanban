#! /usr/bin/php
<?php

use KanbanBoard\Application;
use KanbanBoard\GithubClient;
use KanbanBoard\DataObjects\Issue;
use KanbanBoard\DataObjects\Milestone;

require_once __DIR__.'/../vendor/autoload.php';

$defaultSize = 30;

$repositories = [];
$account;
$token;
$size = $defaultSize;

$shortOpts = "r:a:t:hs:";
$longOpts = [
  "repo:",
  "account:",
  "token:",
  "help",
  "size:",
];



$options = getopt($shortOpts, $longOpts);
foreach($options as $key => $value) {
    switch ($key) {
        case "repo":
        case "r":
            if (is_array($value)) {
                foreach ($value as $repo) {
                    $repositories[] = trim($repo);
                }
            } else {
                $repositories[] = trim($value);
            }
            break;
        case "account":
        case "a":
            $account = trim($value);
            break;
        case "token":
        case "t":
            $token = trim($value);
            break;
        case "size":
        case "s":
            $size = intval($value);
            break;
        case "help":
        case "h":
        default:
            printHelp();
            break;

    }
}

if (empty($repositories)) {
    printHelp();
    die("\nAt least one repository name is required\n");
}

if (empty($token)) {
    printHelp();
    die("\nA personal token to access github is required\n");
}

if (empty($account)) {
    printHelp();
    die("\nThe name of the account owning the github repositories is required\n");
}

if ($size <= 20) {
    printf("Invalid column size %d; using default value of %d", $size, $defaultSize);
    $size = $defaultSize;
}

$format = sprintf("|%%-%ds|%%-%ds|%%-%ds|\n", $size, $size, $size);
$filler = sprintf("|%%'-%ds+%%'-%ds+%%'-%ds|\n", $size, $size, $size);
$github = new GithubClient($token, $account);
$board = new Application($github, $repositories, array('waiting-for-feedback', 'bug', "Feature - Globbing"));
$repositories = $board->board();
foreach($repositories as $repository) {
    printf("Repository: %s\n", $repository->name);
    foreach($repository->milestones as $key => $milestone) {
        printf("Milestone: %s -> $%s\n", $key, $milestone->url);
        printf($format, 'Queued','Active', 'Completed');
        printf($filler, "", "", "");
        $issues = $milestone->getIssues();
        $max = max([count($issues[Issue::ACTIVE]), count($issues[Issue::COMPLETED], count($issues[Issue::QUEUED]))]);
        for ($i = 0; $i < $max; $i++) {
            printf($format, title($i, $issues[Issue::QUEUED], $size), title($i, $issues[Issue::ACTIVE], $size), title($i, $issues[ Issue::COMPLETED], $size));
            printf($filler, "", "", "");
        }
        printf("Completed: %f%%\n", $milestone->progress);
    }
}


function printHelp() {
    print("-r --repo [string] : for each repository to check milestones\n");
    print("-a --account [string] : account name the repositories are under\n");
    print("-t --token [string] : private token to use instead of application token\n");
    print("-h --help: this help\n");
}

function title(int $index, array $issues, int $size) :string {
    $title = "";
    if (array_key_exists($index, $issues)) {
        $title = substr($issues[$index]->title, 0, $size);
    }
    return $title;
}