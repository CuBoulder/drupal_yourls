# drupal_yourls
### Summary
This module provides integration with a YOURLs installation. The only modification to the install is to include the [yourls_api_delete](https://github.com/claytondaley/yourls-api-delete) plugin.
<br>
<br>
This module is designed to provide user role management and authentication. Users must be logged in to access any of the YOURLs features. Users can generate any random short URL ex. sho.rt/a67dg that comes from a colorado.edu domain or subdomain. If a user would like a custom URL, ex. sho.rt/short, they must apply for one. Applications can be reviewd by admins and can be approved or denied. On approval, a new short URL will be generated and an email will be sent to the user notifying them of their new URL. 
<br>
#### Requirements
This module should work with Drupal 8 and 9. You will also have to install the ````drupal/smtp```` module if the custom_urls and approve_urls modules are enabled. The smtp module is used for sending emails through SMTP. 
#### Permissions Added
- Create Random Short URLS: Allows for users to create random short URLs
- Manage Short URL Applications: Allows users to approve/deny applications with a webform
#### This package includes 3 modules
- drupal_yourls : provides a config form to connect to the YOURLs API
- random_urls: provides a page and block to generate random short URLs
- approve_urls_webform: provides a webform and handlers to create/manage applications
#### Short URL Results/Search Block
When the ````random_urls```` module is enabled, users have access to a block called *All Short URLs Block* which shows all of the generated short urls and a search bar.
#### Setup
Go to ````admin/config/services/yourls```` and fill out the form.
<br>

#### Uninstalling Modules
This will delete all of the webform submissions, so please be careful when uninstalling.
