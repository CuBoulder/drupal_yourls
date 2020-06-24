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
- Request Custom URLs: Allows users to fill out applications for custom short URLs 
- Manage Short URL Applications: Allows users to approve/deny applications
#### This package includes 4 modules
- drupal_yourls : provides a config form to connect to the YOURLs API
- random_urls: provides a page to generate random short URLs 
- custom_urls: provides a custom content type and form for users to request a custom short URL
- approve_urls: provides a view to approve custom keywords before generating a short URL
#### Short URL Results/Search Block
When the ````random_urls```` module is enabled, users have access to a block called *All Short URLs Block* which shows all of the generated short urls and a search bar.
#### Setup
Go to ````admin/config/services/yourls```` and fill out the form.
<br>
Once the ````custom_urls```` module is enabled, a few terms will need to be added to the *Application Status Codes* Taxonomy. It needs to be typed exactly like shown below.
- Pending
- Approved
- Rejected

#### Uninstalling Modules
If you would like to uninstall any or all of the modules, some clean up is needed before removing them. The ````approve_urls```` and ````drupal_yourls```` will clean themselves up when uninstalling.
<br>
<br>
Before uninstalling the ````custom_urls```` module, YOU MUST DELETE the *Short URL Application* content type and all of its content. Before uninstalling the ````random_urls```` module, YOU MUST DELETE all instances of the *All Short URLs Block* that may be active on the site. If you don't do this, you cannot reinstall the module without manually removing all of the config files they generated on installation. Deleting these things will save a lot of time.
