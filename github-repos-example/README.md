1. Register application at [https://github.com/settings/developers](https://github.com/settings/developers)
2. Make sure the redirect URI matches the baseURL
3. Make sure you have both php and php-curl installed!
4. Run using `php -s localhost:3000`
   
Before installing cURL the initial auth login with Github worked because the code did not require the use of apiRequest().     
It merely redirects, rather than calls a HTTP request.
```
// Start the login process by sending the user
// to GitHub's authorization page
if(isset($_GET['action']) && $_GET['action'] == 'login') {
    // guarantee client enters flow as unauthenticated 
  unset($_SESSION['access_token']);
 
  // Generate a random hash and store in the session
  $_SESSION['state'] = bin2hex(random_bytes(16));
 
  $params = array(
    'response_type' => 'code',
    'client_id' => $githubClientID,
    'redirect_uri' => $baseURL,
    'scope' => 'user public_repo',
    'state' => $_SESSION['state']
  );
 
  // Redirect the user to GitHub's authorization page
  header('Location: '.$authorizeURL.'?'.http_build_query($params));
  die();
}
```
