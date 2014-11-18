WordPress VIP OAuth2 authentication for a PHP site
==================================================

What is this?
-------------

These PHP scripts allow you to add WordPress OAuth2 authentication to a PHP site that's hosted outside of WordPress. You can ensure that only a recognised user of your WordPress site who is logged in to wordpress.com can access your pages. This has the huge advantage of making WordPress your authentication system: your WordPress site can look after your user accounts, and your users don't have to remember another password.

This method involves several requests between your PHP site and WordPress, but it all happens in the background - the setup is fairly simple.

Please note, this technique will **only work for WordPress VIP clients**. There may be a way to make it work with normal WordPress sites, but I haven't tried to do that. [More details about VIP OAuth2 support.](https://wordpress.com/oauth2/)

Prerequisites
-------------

* These instructions assume you have a PHP site or app you want to add WordPress user authentication to (I'll call it your "external site").
* They also assume you have a second, WordPress site hosted by [WordPress VIP] (http://vip.wordpress.com), **not an ordinary WordPress.com site or self-hosted WordPress site**. This will handle the authentication for your external site.

Check out the repository
------------------------

* Check out the GitHub repository for this application into the root of your hosting environment or a subdirectory of it.
* Open the WordPress page template page-authenticate.php in your editor. Enter two different random phrases as the values for $shared_secret and $key. Keep these phrases private.
* Open config-sample.php and save it as config.php. Copy the shared secret and key you just entered in page-authenticate.php and paste them in the corresponding places in config.php.
* Add page-authenticate.php to your WordPress theme SVN repository and commit it for VIP to deploy to your WordPress root.
* Once the page template is deployed, go into your site's WordPress admin and create a new page using it. Make sure the slug is /authenticate.
  * This will create the endpoint your external site will send requests to to authenticate users. If your external site is example.com and your WordPress site is mywordpress.com, http://example.com/login.php will send authentication requests to http://mywordpress.com/authenticate.

Create a Wordpress.com app
--------------------------

* Now you need to create a WordPress.com application to register some details about the site you want to authenticate. Go to [https://developer.wordpress.com/apps/](https://developer.wordpress.com/apps/).
* Log in using your WordPress.com details.
* Click "Create new application".
* Fill in the resulting form.
  * **Name**, like "Demo application". WordPress displays this name in the authorisation dialogues.
  * **Description**: these further details about your external site are also displayed to help users logging in.
  * **Icon**. You can select a custom icon to identify your site - this is also used in the authorisation dialogues.
  * **Website URL**. URL for a page which gives more info about your external site. This would normally be an About page on your site.
  * **Redirect URI**. URL for a page on your external site which is going to handle the authentication responses from WordPress. This would be something like http://example.com/login.php.
  * **Javascript Origins**. WordPress needs to know what domains to allow Javascript API requests to this app from. Enter the domain of your site, like http://yousite.com.
  * **What is…?** Answer this security question to prove you're a human.
  * **Type**. Select "Native clients".
  * Click the "Create" button. You should now have a WordPress.com app for your external site. Yay!
* Click "My Apps" again, and then the name of your new app. You'll see a page giving the basic app settings. Now you need to grab some of the data from the OAuth Information table to copy to your config file.

Setup in your external site
---------------------------

* Open config.php in your editor.
* Copy the Client ID from the WordPress.com app details and paste it as the value for CLIENT_ID.
* Repeat the process with the Client Secret and Redirect URL.
* For WORDPRESS_DOMAIN give the address of your WordPress.com site that's going to be handling the authentication. It can be a wordpress.com address or a custom address pointing to a wordpress.com site.
* The next item, APP_ROOT, relates to your external site. Leave this blank if your external site lives in a top-level address, otherwise add in the path here, ending in a slash. Example 'myapp/subdirectory/'.
* For $shared_secret and $key, you should the two unique phrases you copied from page-authenticate.php. We'll use these to encrypt and decrypt the communications between your external site and WordPress.
* Include the file authenticate.php in every PHP page you want to add authentication to. See index.php for an example.

How it works
------------

* When a user visits a page which includes authenticate.php, we set a session token called wpcc_state and bounce him to the WordPress.com authentication page, https://public-api.wordpress.com/oauth2/authenticate, sending a number of parameters, including our WordPress client ID and the address of the page wordpress.com should reply to.
* If he's not logged in to the WordPress site he's invited to do so. When he is logged in, he's asked for permission to authenticate him against his WordPress account. When he agrees, wordpress.com sends him back to our REDIRECT_URL login.php, along with a token in the parameter $_GET['state'].
* login.php checks the state parameter matches the wpcc_state token we saved in the session to confirm the request originates from the same user we sent to wordpress.com.
* But wait! WordPress has told us the user's logged in to wordpress.com, but it hasn't sent us much detail about him. We want to know if he's registered as a user on our WordPress site. We need his username! To get his details from the wordpress.com API we need an access token. We send a request to https://public-api.wordpress.com/oauth2/token, sending again our client ID again, the URL of login.php, and the token https://public-api.wordpress.com/oauth2/authenticate sent us.
* If it's happy with the parameters the WordPress API returns the access token we want.
* We now make a request to https://public-api.wordpress.com/rest/v1/me/, sending the access token.
* The public API finally gives us the user's basic details, like display name, username and email address. Phew! Getting there.
* Now we just have to ask our WordPress site if it knows the user. We send his username to the endpoint we made when we created the page using the page-authenticate.php template.
   * To make sure only we can make this type of request, we make a token by hashing a timestamp with the shared secret we created earlier in config.php. We send the token, the timestamp and the username to our WordPress authenticate endpoint, like http://mywordpress.com/authenticate.
* Our WordPress page hashes the timestamp and its own copy of the shared secret to check the token is valid, and uses the timestamp to make sure the request is not too old. If everything's OK, it does a username_exists call to see if the WordPress site has a user with that name.
* If the user checks out, the /authenticate page echoes out the crucial data as JSONP: the token and timestamp sent with our request, and a status string. It encrypts these using the key we set up earlier.
* login.php decrypts this response using its copy of the key we set up. If the status is good it sets a session variable to record the fact and redirects him to the site's main page.

Possible improvements
---------------------

* You could add a timestamp check in login.php so it only accepts recent responses from our WordPress /authenticate endpoint.
* If a user's not authenticated when he requests a protected page, you could save the original request to return him there after successful authentication.
* You could use the $shared_secret for both the token hash and the encryption of the user status response, so you no longer have to enter a $key in the config.
