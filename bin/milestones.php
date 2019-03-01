#! /usr/bin/php
<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../vendor/composer/autoload_classmap.php';

$repositories = [];
$account;
$token;

$shortOpts = "r:a:t:h";
$longOpts = [
  "repo:",
  "account:",
  "token:",
  "help",
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
        case "help":
        case "h":
        default:
            printHelp();
            break;

    }
}

$github = new GithubClient($token, $account);
$board = new \KanbanBoard\Application($github, $repositories, array('waiting-for-feedback'));
$data = $board->board();
foreach($data as $milestone) {
    printf("Milestone: %s\n", $milestone["milestone"]);
    printf("Queued:\n");
    foreach($milestone["queued"] as $queued) {
        printf("%s\n", $queued["title"]);
    }
}


function printHelp() {
    print("-r --repo [string] : for each repository to check milestones\n");
    print("-a --account [string] : account name the repositories are under\n");
    print("-t --token [string] : private token to use instead of application token\n");
    print("-h --help: this help\n");
}
