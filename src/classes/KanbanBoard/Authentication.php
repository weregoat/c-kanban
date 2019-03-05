<?php

namespace KanbanBoard;

use RandomLib\Factory;

class Authentication
{

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
     * Random string to protect against XS attacks.
     * @var string|null
     */
    private $state = NULL;

    /**
     * The scope the OAuth app will require the user authorising the App to grant.
     * Notice that the user authorising the App (through client_id etc.) it doesn't need to be the
     * same owning the repository to display the milestones for.
     * https://developer.github.com/apps/building-oauth-apps/understanding-scopes-for-oauth-apps/
     * @var mixed|string|null
     */
    private $scope = NULL;

    /**
     * Authentication class to perform an OAuth authentication with GitHub to access the API.
     * Once Authorised GitHub would redirect back to the site defined by the OAuth App.
     * Since an OAuth App is supposed to be a *third* application, the whole OAuth App
     * authorisation doesn't fit very well with the test.
     * Hence my adding the possibility to use a personal token instead and specifying the
     * scope.
     * Also why the code was mostly left as it was, at least in the main logic.
     * @param string|null $clientID The clientID as specified in the App authorisation on GitHub
     * @param string|null $clientSecret The Secret as specified in the App authorisation on GitHub
     * @param string|null $scope The scope the App authorisation will have
     */
    public function __construct(string $clientID = null, string $clientSecret = null, string $scope = null)
    {
        if ($clientID == NULL) {
            $clientID = Utilities::env('GH_CLIENT_ID');
        }
        if ($clientSecret == NULL) {
            $clientSecret = Utilities::env('GH_CLIENT_SECRET');
        }
        /* The original application seemed designed to access own repositories.
         * In that light the 'repo' scope would have made sense (although, maybe
         * 'repo:status' would have been better.
         * Since as a coding test this application is required to be published and
         * accessed by someone else, and that I am just using it to access others
         * public repositories as demo, I have added the option to specify the scope the
         * app will ask for to cover the OAuth App authorisation with a more limited
         * scope.
         * Of course with a personal token all this is moot, as the scope is specified elsewhere.
         *
         * https://developer.github.com/apps/building-oauth-apps/understanding-scopes-for-oauth-apps/
         */
        if ($scope == NULL) {
            $scope = Utilities::env('GH_SCOPE', 'repo'); // Notice: defaults to the original value
        }

        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->scope = $scope;

        /* Generate a simple random string to use as state with the auth request */
        /* https://developer.github.com/apps/building-oauth-apps/authorizing-oauth-apps/ */
        $factory = new Factory();
        $generator = $factory->getLowStrengthGenerator();
        $this->state = $generator->generateString(10, "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
    }

    /**
     * Removes the GitHub token from the session.
     */
    public function removeToken()
    {
        unset($_SESSION['gh-token']);
    }

    /**
     * Returns a token from the OAuth App Authorisation with GitHub.
     * @return string|null
     */
    public function getToken()
    {
        session_start();
        $token = NULL;
        if (array_key_exists('gh-token', $_SESSION)) {
            $token = $_SESSION['gh-token'];
        } else if (Utilities::hasValue($_GET, 'code')
            && Utilities::hasValue($_GET, 'state')
            && $_SESSION['redirected']) {
            $_SESSION['redirected'] = false;
            $token = $this->returnsFromGithub($_GET['code']);
            $_SESSION['gh-time'] = time();
        } else {
            $_SESSION['redirected'] = true;
            $this->redirectToGithub();
        }
        $this->removeToken();
        $_SESSION['gh-token'] = $token;
        return $token;
    }

    /**
     * Redirects the web client to GitHub to authorise the App.
     */
    private function redirectToGithub()
    {
        $url = 'Location: https://github.com/login/oauth/authorize';
        $url .= '?client_id=' . $this->clientID;
        if (!empty($this->scope)) {
            $url .= '&scope=' . $this->scope;
        }
        $url .= '&state=' . $this->state;
        header($url);
        exit();
    }

    /**
     * Returns access_code from the query of the callback.
     * @param string $code
     * @return string|null
     */
    private function returnsFromGithub($code)
    {
        $url = 'https://github.com/login/oauth/access_token';
        $data = array(
            'code' => $code,
            'state' => $this->state,
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
        if ($result === FALSE)
            die('Error');
        $accessToken = NULL;
        $matches = array();
        if (preg_match('/access_token=([[:alnum:]]+:?)&/', $result, $matches) == 1) {
            $accessToken = $matches[1];
        };
        return $accessToken;
    }


}
