# drupal_yourls
### Summary
This module provides integration with a YOURLs (>= v1.7.6) installation. There are a few plugins needed for this to all work properly 
 - [yourls_api_delete](https://github.com/claytondaley/yourls-api-delete)
 - [yourls-api-contract](https://github.com/brookestevens/yourls-api-contract)
 - [youls-case-insensitive](https://github.com/adigitalife/yourls-case-insensitive)
<br>
<br>
This module is designed to provide user role management and authentication. Users must be logged in to access any of the YOURLs features. Users can generate any random short URL ex. sho.rt/a67dg that comes from an approved domain set in the config form. If a user would like a custom URL, ex. sho.rt/short, they must apply for one. Applications can be reviewd by admins and can be approved or denied. Last, automatic emails are sent to applicants about their application status.
<br>

#### Requirements
This module should work with Drupal 8 and 9. You will also have to install the ````drupal/smtp```` module if the custom_urls and approve_urls modules are enabled.
#### Permissions Added
- Create Random Short URLS: Allows for users to create random short URLs
- Request Custom Short URLs: Allows users to submit webforms for custom short url applications
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
