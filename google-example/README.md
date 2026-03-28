#### Purpose:
Walkthrough using a simplified OpenID Connect workflow with the Google API to identify the user who signed in to your application.

>>> OAuth was designed as an authorization protocol. So does not tell you *who* the user is. Just gives you an access token to be able to access or modify the user's account.

### Authentication vs Authorisation
- An app that is authenticating users is just verifying who the user is. 
 - An app that is authorizing users is trying to gain access or modify something that belongs to the user.


### Step 1:
Before we can begin, we’ll need to create an application in the Google API Console in order to get a client ID and client secret, and register the redirect URL.    

Visit [https://console.developers.google.com/](https://console.developers.google.com/)and create a new project.       

You’ll also need to create OAuth 2.0 credentials for the project since Google does not do that automatically.   

From the sidebar, click the Credentials tab, then click Create credentials and choose OAuth client ID from the dropdown.


### Step 2: 
Run the `index.php` file with php -s localhost:3000   

Notice that the authorization screen does not look like a typical OAuth screen.   
 
This is because the user isn’t granting any permissions to the application, it’s just trying to identify them.     

When the user selects an account, they will be redirected back to our page with code and state parameters in the request. The next step is to exchange the authorization code for an access token at the Google API.

When Google redirects the user to the redirect URI we gave, Google also 
sends a `code` and `state` parameter. We verify the `state` param matches ours, and we use the `code` to send a POST request for an access token and an id token.
s
Example access token and ID token response after POST request:   
```
{
  "access_token": "ya29.Glins-oLtuljNVfthQU2bpJVJPTu",
  "token_type": "Bearer",
  "expires_in": 3600,
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6Im..."
}
```
The access token should be treated as an opaque string. It has no significant meaning to your app other than being able to use it to make API requests.    

The ID token has a specific structure that your app can parse to find out the user data of who signed in. The ID token is a JWT, explained in more detail in OpenID Connect. You can paste the JWT from Google into a site like [example-app.com/base64](https://example-app.com/base64/) to quickly show you the contents, or you can base64 decode the middle part between the two .‘s to see the user data which we’ll do next.    

JWT always Base64URL encoded and structured as:    
base64url(header) . base64url(payload) . base64url(signature)   

Purpose of Base64 in JWTs:
- Makes binary/JSON data safe to transmit in:
    - HTTP headers
    - URLs
    - cookies  
Ensures consistent formatting across systems   

Once you get an ID token, you should validate it before trusting the information inside.  

##### Two methods of obtaining user info. 
##### 1. Using ID token (JWT payload extraction)
##### 2. Using UserInfo endpoint (API call to tokeninfo endpoint)
- you can use to look up the ID token details instead of parsing it yourself  
- not recommended for production applications, as it requires an additional HTTP round trip, but can be useful for testing and troubleshooting. 
- make a GET request to tokeninfo endpoint with ID token in query string 
- the response is a JSON object with similar list of properties that were included in the JWT itself  
#### 3. Using Access Token 
- many OAuth 2.0 services also provide an endpoint to retrieve the user info of the user who logged in. Make a GET request:
```
GET /oauth2/v3/userinfo
Host: www.googleapis.com
Authorization: Bearer ya29.Gl-oBRPLiI9IrSRA70...
```
The response will be a JSON object with several properties about the user. The response will always include the sub key, which is the unique identifier for the user.    
Google also returns the user’s profile information such as name (first and last), profile photo URL, gender, locale, profile URL, and email. The server can also add its own claims, such as Google’s hd showing the “hosted domain” of the account when using a G Suite account.

Three different ways to get the user’s profile info after the user signs in - which one should you use and when?         

For performance-sensitive applications where you might be reading ID tokens on every request or using them to maintain a session, you should definitely validate the ID token locally rather than making a network request. Google’s API docs provide a good guide on the details of validating ID tokens offline.   

If all you’re doing is trying to find the user’s name and email after they sign in, then extracting the data from the ID token and storing it in your application session is the easiest and most straightforward option.  


Additional notes:    
The best order for this code should be:

```
<?php
session_start();

$authorizeURL = 'https://accounts.google.com/o/oauth2/v2/auth';
$tokenURL = 'https://www.googleapis.com/oauth2/v4/token';
$baseURL = 'http://localhost:3000/index.php';

// 1. logout route
// 2. callback route (?code=...)
// 3. login route (?action=login)
// 4. default page render
```   

Also this code does not handle the logout router.
