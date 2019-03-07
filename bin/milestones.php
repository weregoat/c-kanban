#! /usr/bin/php
<?php

use KanbanBoard\GithubClient;
use KanbanBoard\DataObjects\Issue;
use KanbanBoard\DataObjects\Repository;

require_once __DIR__.'/../vendor/autoload.php';


$repositories = [];
$labels = [];
$account;
$token;

$shortOpts = "r:a:t:l:h";
$longOpts = [
  "repo:",
  "account:",
  "token:",
  "label:",
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
        case "label":
        case "l":
            if (is_array($value)) {
                foreach ($value as $label) {
                    $labels[] = trim(strtolower($label));
                }
            } else {
                $labels[] = trim(strtolower($label));
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
            exit();
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


$github = new GithubClient($token, $account);
foreach($repositories as $name) {
    print("----\n");
    printf("Repository: %s\n", $name);
    $repository = new Repository($name);
    $repository->fetchMilestones($github, $labels);
    foreach($repository->milestones as $key => $milestone) {
        print("*****\n");
        printf(" Milestone: %s (%.0f%% completed) [%s]\n\n", $key, $milestone->progress, $milestone->url);
        foreach([Issue::QUEUED, Issue::ACTIVE, Issue::COMPLETED] as $status) {
            $issues = $milestone->getIssues($status);
            if (!empty($issues)) {
                printf(" %s:\n", ucfirst($status));
                foreach ($issues as $issue) {
                    if ($issue->paused === TRUE) {
                        printf("  -%s (paused)\n", $issue->title);
                    } else {
                        printf("  -%s\n", $issue->title);
                    }
                }
                printf("\n");
            }
        }
        printf("\n");
    }
}


function printHelp() {
    print("-r --repo [string] : for each repository to check milestones from.\n");
    print("-a --account [string] : account name the repositories are under\n");
    print("-t --token [string] : private token to use instead of application token\n");
    print("-l --label [string] : for each label to match for pause status\n");
    print("-h --help: this help\n");
}

