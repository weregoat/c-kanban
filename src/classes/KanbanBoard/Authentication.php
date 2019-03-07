<?php

namespace KanbanBoard;

use RandomLib\Factory;

/**
 * Authentication class to perform an OAuth authentication with GitHub to access the API.
 * https://developer.github.com/v3/guides/basics-of-authentication/
 * @package KanbanBoard
 */
class Authentication
{

    /**
     * Expiration time for the session variables.
     * @var string
     */
    const EXPIRATION_TIMESTAMP = 'kanban_session_expire';

    /**
     * Key to the GitHub API access token in the session.
     * @var string
     */
    const ACCESS_TOKEN = 'kanban_access_token';

    /**
     * If an auth request was performed. Thus requiring to parse the query part for the token.
     * @var string
     */
    const PARSE_QUERY = 'kanban_parse_query';

    /**
     * The key to the auth request state in the session.
     * @var string
     */
    const STATE = 'kanban_state';

    /**
     * Session expiration interval in seconds.
     */
    const EXPIRATION_INTERVAL = 60*60*4; // 4 hours

    /**
     * The client_id to be used with the OAuth authorisation at GitHub.
     * @var mixed|string|null
     */
    private $clientID = NULL;
    /**
     * The client_secret to be used with the OAuth authorisation request at GitHub.
     * @var mixed|string|null
     */
    private $clientSecret = NULL;

    /**
     * The scope the OAuth app will require the user authorising the App to grant.
     * Notice that the user authorising the App (through client_id etc.) doesn't need to be the
     * same owning the repository to display the milestones for.
     * https://developer.github.com/apps/building-oauth-apps/understanding-scopes-for-oauth-apps/
     * @var mixed|string|null
     */
    private $scope = NULL;

    /**
     * Constructor; where optionally client_id, client_secret and scope for the authorisation with
     * GitHub API can be specified instead of the environment variables.
     *
     * @param string $clientID The clientID as specified in the App authorisation on GitHub
     * @param string $clientSecret The Secret as specified in the App authorisation on GitHub
     * @param string|null $scope The scope the App authorisation will have
     * @throws \RuntimeException if no client_id or client_secret can be defined by parameters or environment variables.
     * @uses Utilities::env
     */
    public function __construct(string $clientID, string $clientSecret, string $scope = NULL)
    {
        /* The original application seemed designed to access own repositories.
         * In that light the 'repo' scope would have made sense (although, maybe,
         * 'repo:status' would have been better).
         * Since as a coding test this application is required to be published and
         * accessed by someone else, and that I am just using it to access others
         * public repositories as demo, I have added the option to specify the scope the
         * app will ask for to cover the OAuth App authorisation with a more limited
         * scope.
         * Of course with a personal token all this is moot, as the scope is specified elsewhere.
         *
         * https://developer.github.com/apps/building-oauth-apps/understanding-scopes-for-oauth-apps/
         */
        if ($scope === NULL) {
            $scope = 'repo'; // Original value as default.
        }

        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->scope = $scope;

    }

    /**
     * Returns a token from the OAuth App Authorisation with GitHub.
     * @return string|null
     * @throws \Exception
     * @url https://developer.github.com/apps/building-oauth-apps/authorizing-oauth-apps/
     */
    public function getToken()
    {
        session_start();
        $this->checkSessionExpiration();

        $token = Utilities::getValue($_SESSION, self::ACCESS_TOKEN);
        /* If we don't have a token in the session we need to get a new one from GitHub */
        if (empty($token)) {
            /* If this is a callback from GitHub after the client authorised the app */
            if (Utilities::hasValue($_GET, 'code') AND Utilities::hasValue($_GET, 'state')
                /* And we were expecting it */
                AND $_SESSION[self::PARSE_QUERY] === TRUE)
            {
                /* Then we request the token from GitHub using the code */
                $token = $this->returnFromGithub($_GET['code'], $_GET['state']);
            } else {
                /* Otherwise we redirect the client to GitHub to authorise the app */
                $this->redirectToGithub();
            }
        }
        return $token;
    }

    /*
     * Removes the variables from the session. Thus forcing requesting a new token.
     */
    public function clearSession() {
        unset($_SESSION[self::ACCESS_TOKEN]);
        unset($_SESSION[self::PARSE_QUERY]);
        unset($_SESSION[self::EXPIRATION_TIMESTAMP]);
        unset($_SESSION[self::STATE]);
    }

    /**
     * Redirects the web client to GitHub to authorise the App.
     * @url https://developer.github.com/apps/building-oauth-apps/authorizing-oauth-apps/#1-request-a-users-github-identity
     */
    private function redirectToGithub()
    {

        $url = 'Location: https://github.com/login/oauth/authorize';
        $url .= '?client_id=' . $this->clientID;
        if (!empty($this->scope)) {
            $url .= '&scope=' . $this->scope;
        }
        /* Generate a simple random string to use as state with the auth request */
        /* https://developer.github.com/apps/building-oauth-apps/authorizing-oauth-apps/ */
        $factory = new Factory();
        $generator = $factory->getLowStrengthGenerator();
        $state = $generator->generateString(10, "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $url .= '&state=' . $state;
        $_SESSION[self::STATE] = $state;
        $_SESSION[self::PARSE_QUERY] = TRUE;
        header($url);
        exit();
    }

    /**
     * Returns access_code from the query of the callback.
     * @param string $code
     * @param string $state
     * @return string|null
     * @throws \Exception
     * @url https://developer.github.com/apps/building-oauth-apps/authorizing-oauth-apps/#2-users-are-redirected-back-to-your-site-by-github
     */
    private function returnFromGithub(string $code, string $state)
    {
        /*
            https://developer.github.com/apps/building-oauth-apps/authorizing-oauth-apps/#2-users-are-redirected-back-to-your-site-by-github
        */
        if ($state !== $_SESSION[self::STATE]) {
            throw new \Exception("Invalid state");
        }
        $url = 'https://github.com/login/oauth/access_token';
        $data = array(
            'code' => $code,
            'state' => $_SESSION['gh-state'],
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            throw new \Exception("Failed to process OAuth request to GitHub");
        }
        $accessToken = NULL;
        $matches = array();
        if (preg_match('/access_token=([[:alnum:]]+:?)&/', $result, $matches) == 1) {
            $accessToken = $matches[1];
        };
        $_SESSION[self::ACCESS_TOKEN] = $accessToken;
        /* We don't need to ask for auth again */
        $_SESSION[self::PARSE_QUERY] = FALSE;
        return $accessToken;
    }

    /**
     * Check the expiration timestamp for the session variables.
     */
    private function checkSessionExpiration()
    {
        $now = time();
        if (!Utilities::hasValue($_SESSION, self::EXPIRATION_TIMESTAMP)) {
            $_SESSION[self::EXPIRATION_TIMESTAMP] = $now + self::EXPIRATION_INTERVAL;
        }
        if (Utilities::getValue($_SESSION, self::EXPIRATION_TIMESTAMP) < $now) {
            $this->clearSession();
            $_SESSION[self::EXPIRATION_TIMESTAMP] = $now + self::EXPIRATION_INTERVAL;
        }
    }



}
