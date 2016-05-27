<?php
define('MAX', 200);

session_start();
require_once 'client/vendor/autoload.php';

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => 'ID',    // The client ID assigned to you by the provider
    'clientSecret'            => 'SECRET',   // The client password assigned to you by the provider
    'redirectUri'             => 'http://thibault.com/refresh.php',
    'urlAuthorize'            => 'https://accounts.google.com/o/oauth2/auth',
    'urlAccessToken'          => 'https://accounts.google.com/o/oauth2/token',
	'urlResourceOwnerDetails' => 'https://www.googleapis.com/analytics/v3/data/realtime?ids=ga%3A53508797&metrics=rt%3Alatitude%2Crt.longitude',
	'verify'                  => false
]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {
	unset($_SESSION['token']);
	unset($_SESSION['access']);
    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
		$options = [
    'scope' => 'https://www.googleapis.com/auth/analytics'
	];

    $authorizationUrl = $provider->getAuthorizationUrl($options);

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    try {
		
		if(!isset($_SESSION['access']))
		{
			// Try to get an access token using the authorization code grant.
			$accessToken = $provider->getAccessToken('authorization_code', [
				'code' => $_GET['code']
			]);
			
			$_SESSION['access'] = serialize($accessToken);
		}
		else{
			$accessToken = unserialize($_SESSION['access']);
		}       

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.       
		if($accessToken->hasExpired())
		{
			$accessToken->getRefreshToken();
		}			
				
		$_SESSION['token'] = 'Bearer ' . $accessToken->getToken();

        // The provider provides a way to get an authenticated API request for
        // the service, using the access token; it returns an object conforming
        // to Psr\Http\Message\RequestInterface.
		header('Location: data.php');

    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Failed to get the access token or user details.
        exit($e->getMessage());

    }

}
