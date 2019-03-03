<?php
namespace KanbanBoard;

use RandomLib\Factory;

class Authentication {

    private $clientID = NULL;
	private $clientSecret = NULL;
	private $state = NULL;

	public function __construct(string $clientID = null, string $clientSecret = null)
	{
	    if ($clientID == NULL) {
            $clientID = Utilities::env('GH_CLIENT_ID');
        }
	    if ($clientSecret == NULL) {
            $clientSecret = Utilities::env('GH_CLIENT_SECRET');
        }
	    $this->clientID = $clientID;
	    $this->clientSecret = $clientSecret;
	    $factory = new Factory();
	    $generator = $factory->getLowStrengthGenerator();
	    $this->state = $generator->generateString(10,"0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
	}

	public function removeToken()
	{
		unset($_SESSION['gh-token']);
	}

	public function getToken()
	{
		session_start();
		$token = NULL;
		if(array_key_exists('gh-token', $_SESSION)) {
			$token = $_SESSION['gh-token'];
		}
		else if(Utilities::hasValue($_GET, 'code')
			&& Utilities::hasValue($_GET, 'state')
			&& $_SESSION['redirected'])
		{
			$_SESSION['redirected'] = false;
			$token = $this->returnsFromGithub($_GET['code']);
		}
		else
		{
			$_SESSION['redirected'] = true;
			$this->redirectToGithub();
		}
		$this->removeToken();
		$_SESSION['gh-token'] = $token;
		return $token;
	}

	private function redirectToGithub()
	{
		$url = 'Location: https://github.com/login/oauth/authorize';
		$url .= '?client_id=' . $this->clientID;
		$url .= '&scope=repo';
		$url .= '&state=' . $this->state;
		header($url);
		exit();
	}

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
		$result = explode('=', explode('&', $result)[0]);
		array_shift($result);
		return array_shift($result);
	}


}
