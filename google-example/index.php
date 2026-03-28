<?php

// Fill these out with the values you got from Google
$googleClientID = '';
$googleClientSecret = '';
 
// This is the URL we'll send the user to first
// to get their authorization
$authorizeURL = 'https://accounts.google.com/o/oauth2/v2/auth';
 
// This is Google's OpenID Connect token endpoint
$tokenURL = 'https://www.googleapis.com/oauth2/v4/token';
 
// The URL for this script, used as the redirect URL
$baseURL = 'http://localhost:3000/index.php';
 
// Start a session so we have a place
// to store things between redirects
session_start();


// When Google redirects the user back here, there will
// be a "code" and "state" parameter in the query string
// so once user gets sent back to redirect URI, get the ID token
// from Google using the code they gave us 
if(isset($_GET['code'])) {
  // Verify the state matches our stored state
  if(!isset($_GET['state']) || $_SESSION['state'] != $_GET['state']) {
    header('Location: ' . $baseURL . '?error=invalid_state');
    die();
  }
 
  // Exchange the authorization code for an access token
  // build up a POST request to Google’s token endpoint containing 
  // our app’s client ID and secret, plus the authorization code that 
  // Google sent back to us in the query string.

  $ch = curl_init($tokenURL);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'client_id' => $googleClientID,
    'client_secret' => $googleClientSecret,
    'redirect_uri' => $baseURL,
    'code' => $_GET['code']
  ]));

  $data = json_decode(curl_exec($ch), true);
 
  // after getting the id token, 
  // Split the JWT string into three parts
  $jwt = explode('.', $data['id_token']);
 
  // Extract the middle part, base64 decode, then json_decode it
  // choose index 1 because we know jwt is always structered in 3
  // parts: header.payload.signature
  $userinfo = json_decode(base64_decode($jwt[1]), true);
 
  // The sub (subject) property contains the unique user 
  // identifier of the user who signed in.
  // store in session to indicate to our app that the user is signed in.
  $_SESSION['user_id'] = $userinfo['sub'];
  $_SESSION['email'] = $userinfo['email'];
 
  // While we're at it, let's store the access token and id token
  // so we can use them later
  $_SESSION['access_token'] = $data['access_token'];
  $_SESSION['id_token'] = $data['id_token'];
 
  header('Location: ' . $baseURL);
  die();
}

// Start the login process by sending the user
// to Google's authorization page
// build up an authorization URL and then send the user there
if(isset($_GET['action']) && $_GET['action'] == 'login') {
  unset($_SESSION['user_id']);
 
  // Generate a random string and store in the session
  $_SESSION['state'] = bin2hex(random_bytes(16));
 
  // response_type=code parameter indicates
  // we want Google to return an authorization 
  // code that we’ll exchange for the id_token later.
  $params = array(
    'response_type' => 'code',
    'client_id' => $googleClientID,
    'redirect_uri' => $baseURL,
    'scope' => 'openid email',
    'state' => $_SESSION['state']
  );
 
  // Redirect the user to Google's authorization page
  header('Location: '.$authorizeURL.'?'.http_build_query($params));
  die();
}

// If there is a user ID in the session
// the user is already logged in
if(!isset($_GET['action'])) {
  if(!empty($_SESSION['user_id'])) {
    echo '<h3>Logged In</h3>';
    echo '<p>User ID: '.$_SESSION['user_id'].'</p>';
    echo '<p>Email: '.$_SESSION['email'].'</p>';
    echo '<p><a href="?action=logout">Log Out</a></p>';
 
    // Fetch user info from Google's userinfo endpoint
    echo '<h3>User Info</h3>';
    echo '<pre>';
    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer '.$_SESSION['access_token']
    ]);
    curl_exec($ch);
    echo '</pre>';
 
  } else {
    echo '<h3>Not logged in</h3>';
    echo '<p><a href="?action=login">Log In</a></p>';
  }
  die();
}

