=== WordPress + Microsoft Office 365 / Azure AD | LOGIN ===
Contributors: wpo365
Tags: office 365, O365, Microsoft 365, azure active directory, Azure AD, AAD, authentication, single sign-on, sso, SAML, SAML 2.0, OpenID Connect, OIDC, login, oauth, microsoft, microsoft graph, teams, microsoft teams, sharepoint online, sharepoint, spo, onedrive, SCIM, User synchronization, yammer, powerbi, power bi, mail, smtp, phpmailer, wp_mail, email
Requires at least: 4.8.1
Tested up to: 6.0
Stable tag: 19.4
Requires PHP: 5.6.40
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

With WPO365 | LOGIN users can sign in with their corporate or school (Azure AD / Microsoft Office 365) account to access your WordPress website: No username or password required (OIDC or SAML 2.0 based SSO). Plus you can send email using Microsoft Graph instead of SMTP from your WordPress website.

= SINGLE SIGN-ON (SSO) =

- Supported Identity Providers (IdPs): **Azure Active Directory** and **Azure AD B2C** [more](https://docs.wpo365.com/article/158-select-identity-provider-idp)
- Supported SSO protocols: **OpenID Connect** and **SAML 2.0** [more](https://docs.wpo365.com/article/159-select-sso-protocol)
- Supported OpenID Connect User Flows: Authorization Code User Flow (recommended) and Hybrid User Flow [more](https://docs.wpo365.com/article/156-why-the-authorization-code-user-flow-is-now-recommended)

= NEW USERS =

- New users that sign in with Microsoft automatically become WordPress users [more](https://www.wpo365.com/sso-for-office-365-azure-ad-user/)

= INTRANET =

- Configure the **intranet** authentication mode to restrict access to all front-end posts and pages [more](https://www.wpo365.com/make-your-wordpress-intranet-private/)
- Hide the  **WordPress Admin Bar** for specific roles [more](https://docs.wpo365.com/article/150-hide-wp-admin-bar-for-roles)

= MICROSOFT TEAMS =

- Support for (seamless) integration of your WordPress website into a **Microsoft Teams** Tabs and Apps [more](https://docs.wpo365.com/article/70-adding-a-wordpress-tab-to-microsoft-teams-and-use-single-sign-on)

= MAIL =

- **Send emails using Microsoft Graph** instead of SMTP from your WordPress website [more](https://docs.wpo365.com/article/108-sending-wordpress-emails-using-microsoft-graph)
- Send as **HTML**
- Save to the **Sent Items** folder
- Support for **file attachments**

= WORDPRESS MULTISITE =

- Support for **WordPress Multisite** [more](https://www.wpo365.com/support-for-wordpress-multisite-networks/)

= POWER BI =

- Embed Microsoft **Power BI** content (user owns data) [more](https://www.wpo365.com/power-bi-for-wordpress/)

= SHAREPOINT =

- Embed a **SharePoint Online** library using a [Gutenberg Block](https://docs.wpo365.com/article/128-embedded-sharepoint-library-powerful-gutenberg-blocks-for-wordpress) or as [simple shortcode](https://docs.wpo365.com/article/126-sharepoint-library-for-wordpress)
- Embed a **SharePoint Online** search experience into a front-end post or page using simple to generate shortcode [more](https://www.wpo365.com/content-by-search/)

= EMPLOYEE DIRECTORY =

- Embed an intuitve Azure AD / Microsoft Graph based **Employee Directory** into a front-end post or page [more](https://www.wpo365.com/employee-directory/)

= REST API ENDPOINT PROTECTION =

- Protect your **WordPress REST API** endpoints with a combination of a WordPress cookie and a nonce for delegated access [more](https://docs.wpo365.com/article/151-wordpress-cookies-based-protection-for-the-wordpress-rest-api)

= DEVELOPERS =

- Developers can now connect to a RESTful API for Microsoft Graph in their favorite programming language and without the hassle of authentication and authorization [more](https://docs.wpo365.com/article/129-a-restful-proxy-to-microsoft-graph-inside-wordpress)
- *PHP hooks* for developers to build custom Microsoft Graph / Office 365 integrations [more](https://docs.wpo365.com/article/82-developer-hooks)

https://youtu.be/6ti71O4nh0s

== ADD FUNCTIONALITY WITH PREMIUM EXTENSIONS ==

= PROFILE+ =

- Update a WordPress **user profile** with (first, last, full) name, email and UPN from Azure AD [more](https://www.wpo365.com/downloads/wordpress-office-365-login-plus/)

= SINGLE SIGN-ON =

- Visitors are required to sign in with Azure AD / Microsoft but will not be automatically logged in to WordPress [more](https://docs.wpo365.com/article/36-authentication-scenario)

= AUDIENCES =

- Azure AD group based **access restriction** for individual front-end posts and pages [more](https://docs.wpo365.com/article/139-audiences)

= SYNC =

- On-demand / scheduled **user synchronization** from Azure AD to WordPress [more](https://www.wpo365.com/synchronize-users-between-office-365-and-wordpress/)

= AVATAR =

- Replace the default WordPress / BuddyPress **avatar** with a Microsoft 365 profile picture [more](https://www.wpo365.com/downloads/wpo365-avatar/)

= ROLES + ACCESS =

- WordPress roles assignments / access restrictions based on *Azure AD groups* / user attributes [more](https://www.wpo365.com/downloads/wpo365-roles-access/)

= LOGIN+ =

- Enrich a user's WordPress (or BuddyPress) profile with user attributes pulled from Azure AD / Microsoft 365 [more](https://docs.wpo365.com/article/98-synchronize-microsoft-365-azure-ad-profile-fields)
- Support for so-called **Multi-Tenancy** [more](https://docs.wpo365.com/article/47-support-for-multitenant-apps)
- Map **custom claims** sent in an Azure AD B2C ID token to custom WordPress user profile fields [more](https://docs.wpo365.com/article/130-azure-ad-b2c-based-single-sign-on-for-wordpress)
- Require **Proof Key for Code Exchange (PKCE)** for increased protection when requesting oauth tokens from Azure AD [more](https://docs.wpo365.com/article/149-require-proof-key-for-code-exchange-pkce)
- Other features: [Enable SSO for the login page](https://docs.wpo365.com/article/120-enable-sso-for-the-default-custom-login-page), [Dual login](https://docs.wpo365.com/article/81-enable-dual-login) and [Private Pages](https://docs.wpo365.com/article/38-private-pages)

= MAIL =

- Allow forms / plugins / themes to dynamically set the **From** address
- **Log every email** sent from your WordPress website, review errors and try to send unsuccessfully sent mails again. [more](https://www.wpo365.com/downloads/wpo365-mail/)
- Send all emails by default as BCC

= GROUPS =

- Deep integration with the **(itthinx) Groups** plugin for group membership and access control [more](https://www.wpo365.com/downloads/wpo365-groups/)

= MICROSOFT 365 APPS =

- Advanced versions of the apps to embed content of Microsoft 365 services such as **Power BI** (with support for *application owns data* scenarios) and **SharePoint Online** (with support for anonymous users)

= SCIM =

- (SCIM based) **Azure AD User Provisioning** to WordPress [more](https://www.wpo365.com/downloads/wpo365-scim/)

= REST API ENDPOINT PROTECTION =

- Enable **Azure AD** based protection for your **WordPress REST API** endpoints [more](https://docs.wpo365.com/article/147-azure-ad-based-protection-for-the-wordpress-rest-api)

= CONFIGURATION =

- Save multiple configurations
- Directly edit (the JSON representation of) a configuration

== Prerequisites ==

- Make sure that you have disabled caching for your Website in case your website is an intranet and access to WP Admin and all pubished pages and posts requires authentication. With caching enabled, the plugin may not work as expected
- We have tested our plugin with Wordpress >= 4.8.1 and PHP >= 5.6.40
- You need to be (Office 365) Tenant Administrator to configure both Azure Active Directory and the plugin
- You may want to consider restricting access to the otherwise publicly available wp-content directory

== Support ==

We will go to great length trying to support you if the plugin doesn't work as expected. Go to our [Support Page](https://www.wpo365.com/how-to-get-support/) to get in touch with us. We haven't been able to test our plugin in all endless possible Wordpress configurations and versions so we are keen to hear from you and happy to learn!

== Feedback ==

We are keen to hear from you so share your feedback with us on [Twitter](https://twitter.com/WPO365) and help us get better!

== Open Source ==

When you’re a developer and interested in the code you should have a look at our repo over at [WordPress](http://plugins.svn.wordpress.org/wpo365-login/).

== Installation ==

Please refer to [these **Getting started** articles](https://docs.wpo365.com/category/21-getting-started) for detailed installation and configuration instructions.

== Frequently Asked Questions ==

== Screenshots ==
1. Microsoft / Azure AD based Single Sign-on
2. Embedded Power BI for WordPress
3. Embedded SharePoint Online Documents for WordPress
4. Embedded SharePoint Online Search for WordPress
5. Employee Directory
6. Support for Azure AD B2B and Azure AD B2C
7. Sending WordPress email using Microsoft Graph
8. Synchronizing users from Azure AD to WordPress
9. Embed WordPress in a Teams Tab or App
10. Assign WordPress roles / Deny access based on Azure AD groups

== Upgrade Notice ==

* Please check the online version of the [release notes for version 11.0](https://www.wpo365.com/release-notes-v11-0/).

== Changelog ==

= v19.4 =
* Fix: Mail authorization for delegated access would fail with the error "Could not retrieve a tenant and application specific JSON Web Key Set and thus the JWT token cannot be verified successfully". [LOGIN, MICROSOFT GRAPH MAILER]
* Fix: Embedded PowerBI reports will now try to refresh the acquired access token when the browser tab is left open. [LOGIN, INTRANET, M365 APPS]
* Fix: Encoding of parameters for embedded SharePoint Online apps (Search and Documents) have been improved. [LOGIN, INTRANET, M365 APPS]
* Fix: The Audiences custom meta box has been updated and produces valid HTML. [ROLES + ACCESS, SYNC, INTRANET]

= v19.3 =
* Fix: The delegated mail authorization feature would - under circumstances - fail to get the mail specific tenant ID and as a result an attempt to refresh the access token may fail. [LOGIN, MICROSOFT GRAPH MAILER]

= v19.2 =
* Fix: The Redirect URL field for the mail authorization is no longer greyed out and can be changed by administrators. [LOGIN]

= v19.1 =
* Fix: A backward-compatibility issue with Audiences would cause a critical error when editing a page. Administrators with any of the following extensions installed must update as soon as possible: ROLES + ACCESS, SYNC, INTRANET.

= v19.0 =
* Change: Sending WordPress emails using Microsoft Graph can now also be configured with **delegated** permissions. Administrators are urged to review the (documentation)[https://docs.wpo365.com/article/108-sending-wordpress-emails-using-microsoft-graph] and to update their configuration. [LOGIN, MICROSOFT GRAPH MAILER]
* Feature: **Audiences** - used to target posts and pages to specific Azure AD groups - can now also be used on a post or page using a custom metabox in the sidebar. Consult the (updated documentation)[https://docs.wpo365.com/article/139-audiences] for details. [ROLES + ACCESS, SYNC, INTRANET]
* Feature: Azure Active Directory secrets can now be stored in the website's **WP-Config.php** and removed from the database. [MAIL]
* Improvement: A number of **plugin self-tests** have been improved to help administrators find loopholes in the configuration e.g. of User synchronization and the integration of various SharePoint Online services. [LOGIN]
* Fix: The plugin no longer "hijacks" a state parameter when sent in the header of any request. This prevented - amongst other things - enabling / disabling of WordPress auto-updates. [LOGIN]
* Fix: The **Employee Directory** app now shows profile information when users are searched for using SharePoint. [M365 APPS, INTRANET]
* Fix: Version bump for all WPO365 plugins.

= v18.2 =
* Fix: Recent changes to the built-in notification service could cause a fatal error for older PHP versions that has now been fixed. [LOGIN]

= v18.1 =
* Fix: If the plugin is configured to send WordPress emails using Microsoft Graph then it will now always replace the "from" email address if WordPress tries to sent emails from "wordpress@[sitename]". WordPress will propose this email address is no email is set by the plugin sending the email (e.g. Contact Form 7). This email may pass checks as a valid email address but in reality this email address most likely does not exist. The option to fix the "localhost" issue has been removed since this fix improves the behavior for all hosts (incl. localhost). [ALL]
* Improvement: Various wp-admin banners as well as some translations have been updated. Also a teaching bubble is shown on the Single Sign-on page to help admins quickly find the WPO365 documentation center at [https://docs.wpo365.com/](https://docs.wpo365.com/). [ALL]

= v18.0 =
* Change: Administrators who selected **OpenID Connect** based single sign-on, can now choose between the **Hybrid Flow** and the **Authorization Code Flow**. New installations will automatically be configured using Authorization Code Flow. [Read more](https://docs.wpo365.com/article/153-hybrid-flow-versus-authorization-code-flow) [LOGIN]
* Change: Support for **Azure AD B2C** custom policies (sign-up, sign-in and password reset) is no longer a premium feature. [LOGIN]
* Change: All features of **WPO365 | CUSTOM USER FIELDS** extension are from now on supported by the **WPO365 | LOGIN+ extension**. See [our website](https://www.wpo365.com/downloads/wordpress-office-365-login-professional/) for details and pricing. [CUSTOM USER FIELDS, LOGIN+]
* Change: A new **WPO365 Features Dashboard** has been added that allows administrators to toggle features such as e.g. SSO, MAIL and SYNC on or off. [LOGIN]
* Feature: Admins can now choose to hide the **WordPress Admin Bar** for specific roles. [LOGIN]
* Feature: Requesting access tokens from Azure AD can now be further secured using a **Proof Key for Code Exchange (PKCE)**. [LOGIN+, SYNC, INTRANET]
* Feature: Protect and secure your **WordPress REST API** with Azure AD generated oauth access tokens (PREMIUM). [LOGIN+, SYNC, INTRANET]
* Feature: Protect and secure your **WordPress REST API** with WordPress REST cookies. [LOGIN]
* Improvement: **Azure AD B2C** custom claims sent in the ID token can now be mapped to custom WordPress user meta fields. [LOGIN+, SYNC, INTRANET]
* Improvement: When specified in - for example - an email form the "From" address will be used to send the email from (instead of the configured "From" address and if the address specified in the form appears to be valid). This behavior is a premium feature and not enabled by default. [MAIL, SYNC, INTRANET]
* Improvement: Admins can now set a different Azure AD tenant for sending WordPress emails using Microsoft Graph when the plugin is configured for **Azure AD B2C based** single sign-on. [ALL]
* Improvement: Admins can now update the priority for the get_avatar hook on the plugin's *User sync* page (default 1). [AVATAR, SYNC, INTRANET]
* Improvement: The plugin is now able to work with the more appropriate **GroupMember.Read.All** permissions instead of *Group.Read.All* and admins who configured role based access restriction are advised to update the **API permissions** for the registered application in *Azure AD*. [ROLES+ACCESS, SYNC, INTRANET]
* Fix: The logic to detect the blog ID in a WordPress Multisite (WPMU) will always test with a trailing slash. [LOGIN]
* Fix: A (custom) login message - for example created with LoginPress - will now show as expected. [ALL]
* Fix: Non-dynamic roles in an identities configuration used to enable RLS when embedding Power BI content no longer causes a fatal error. [M365 APPS, INTRANET]
* Fix: It is now possible to save empty custom user profile fields when manually updating a user's profile. [CUSTOM USER FIELDS, SYNC, INTRANET]

= v17.5 =
* Change: Sending mail as HTML is no longer a premium feature. [LOGIN, MAIL]
* Change: Saving sent emails in Sent Items is no longer a premium feature. [LOGIN, MAIL, SYNC, INTRANET]
* Change: The plugin will now always force the use of the WPO365 avatar feature if this enabled by removing other similar filters. [AVATAR, SYNC, INTRANET]
* Improvement: Administrators can now configure the WPO365 plugin to prevent WordPress sending a notification to admins when a user changes a password. [LOGIN+, SYNC, INTRANET]
* Improvement: The Graph Mailer components have been refactored for improved logging / auditing. [LOGIN, MAIL, SYNC, INTRANET]
* Fix: Login button label's text will not be wrapped on a new line. [LOGIN]
* Fix: The premium mail function to log all emails sent did not properly return and when used in combination with another mail plugin - e.g. WP HTML Mail - emails were not sent as expected. [MAIL, SYNC, INTRANET]
* Fix: The mail-log table can now be scrolled horizontally. [MAIL, SYNC, INTRANET]
* Fix: Sending a test email with attachment is now supported by all versions. [LOGIN]
* Fix: The plugin will not try and send attachments larger than 3 Mb (the prevent the mail being refused by the Microsoft Graph API). [LOGIN]
* Fix: The Azure AD based Employee Directory app will now retrieve the user's profile and cache them separately from the cache of employees as this sometimes would break the hierarchy. [M365 APPS, INTRANET]

= v17.4 =
* Improvement: You can now create a shortcode based app to embed Power BI content that supports the "App owns data" scenario and configure it to use the 2nd (app-only) AAD App registration. This is especially useful when the administrator configured Microsoft Azure AD B2C or SAML 2.0 based single sign-on and the AAD App registration for SSO cannot be used. [M365 APPS, INTRANET]
* Fix: Several issues related to PHP 8.x - e.g. support for deep-links - have been fixed. [ALL]
* Fix: When an administrator has hidden the login button the artifacts required to initiate the WPO365 Sign in with Microsoft flow - e.g. needed for a custom login button or Dual Login - are now loading correctly. [LOGIN]

= v17.3 =
* Change: The default login button's accessibility has been improved and now uses a BUTTON element (previously a DIV) with an aria-label that corresponds to the button's caption. [LOGIN]
* Improvement: For new installations of the plugin the default value of the session duration has been updated to 0 (was 3600) and a user will not be automatically forced to re-authenticate after 1 hour. [LOGIN]
* Fix: The (basic / free version of the) **Microsoft 365 shortcode app to embed a SharePoint / OneDrive Documents Library** now correctly recognizes when it should use the new *WPO365 REST API for Microsoft Graph* instead of the older token service. [LOGIN]
* Fix: The exception level for "Serious attempt to try to bypass authentication" has been changed from ERROR to WARN since many false/positives have been reported. [LOGIN]

= v17.2 =
* Improvement: When the WPO365 plugin is used to send WordPress emails, it will now honor a reply-to email address defined "externally" e.g. when using Contact Form 7. [ALL]
* Fix: The **Microsoft 365 shortcode app for SharePoint Online Search** now properly encodes the query template / custom written query to enable the use of double quotes when defining values for a managed property e.g. *Path:"https://wpo365demo.sharepoint.com/sites/contoso/Shared Documents"*. To benefit from this you must re-create the shortcode for the app. [M365 APPS, INTRANET]
* Fix: When using the *Dual Login* Feature the plugin no longer adds an HTML DIV element with a duplicate ID. [LOGIN+, SYNC, INTRANET]

= v17.1 =
* Change: The **Microsoft 365 shortcode app for embedding Power BI** has been thoroughly updated to work with the recently introduced *WPO365 REST API for Microsoft Graph*. Existing shortcodes will continue to work with the WordPress AJAX API but it is recommended to update those apps by creating new shortcodes using the Shortcode Generator. [M365 APPS, INTRANET]
* Change: The **Microsoft 365 shortcode app for embedding Power BI** now supports dynamic tokens used when defining **Effective Identities** when row-level-security (RLS) is configured for the dataset. Dynamic tokes can be replaced with direct attributes of a WordPress user or alternatively user meta. Please refer to the [updated documentation](https://docs.wpo365.com/article/84-microsoft-power-bi-for-wordpress) for guidance how to use dynamic tokens. [M365 APPS, INTRANET]
* Improvement: Newly created **Power BI shortcode apps** will cache access tokens in the browser's session storage and this cache will be emptied when the user navigates away from the page. [APPS, INTRANET]
* Fix: The license checker will not show a warning for development, test and staging environments if those web addresses can be identified as such. [ALL PREMIUM]
* Fix: Use the home URL instead of the site URL (where WordPress application files are accessible) for the base URI when connecting to the WPO365 REST API for Microsoft Graph. [ALL]
* Fix: Administrators can activate **Internet Explorer 11 compatibility** mode (at the cost of losing the integration capability with Microsoft Teams) when they go to WP Admin > WPO365 > ... > Miscellaneous and toggle the corresponding option. [ALL]

= v17.0 =
* Breaking: Additional configuration is needed for the **WPO365 REST API for Microsoft Graph** that is used by the *WPO365 | DOCUMENTS Gutenberg Block*, *WPO365 User synchronization* and the updated versions of the *M365 APPS* (included in this release) to keep your data as safe and secure as possible. On the plugin's *Integration* page in the section labelled *Microsoft 365 Apps* you must now specify whether user must be signed in (with Microsoft) when they connect to the API, which Microsoft Services endpoints are allowed, whether apps may request application-level tokens (instead of user-level tokens with delegated permissions) and whether proxy-type of requests are permitted. [ALL]
* Breaking: Because the direct use of **PHP cURL has been removed** in favor of WordPress' builtin HTTP API the cURL Proxy Address setting has been removed. Please refer to the [WordPress documentation](https://developer.wordpress.org/reference/classes/wp_http_proxy/) for instructions how you can use WP_PROXY_HOST and WP_PROXY_PORT if you need to configure a proxy for outgoing network connections. [ALL]
* Breaking: The [End User License Agreement](https://www.wpo365.com/end-user-license-agreement/) will be updated from 1st April 2022 and a license for a premium extension will always be linked to the domain of the WordPress instance. Therefore you may need additional licenses for development, test and staging instances if those are set up under different domains. Starting with this version premium extensions / bundles will generate a warning that a license is required if no valid license was found for that particular instance. [ALL EXTENSIONS / BUNDLES]
* Change: The Microsoft 365 shortcode apps for embedding *SharePoint Online Search*, a *SharePoint / OneDrive Online Document Library*, a *Microsoft Graph based Employee Directory* and a *Yammer Feed* have been thoroughly updated to work with the recently introduced *WPO365 REST API for Microsoft Graph*. Existing apps will continue to work with the WordPress AJAX API but it is recommended to update those apps by creating new shortcodes using the Shortcode Generators. [M365 APPS, INTRANET]
* Improvement: The **WPO365 SCIM Client for Azure AD User provisioning** now can be configured to obtain to retrieve the user's *Azure AD object ID*. This is needed if the plugin needs to retrieve additional user attributes, a user's profile picture or Azure AD group memberships from Microsoft Graph. see the *Troubleshooting* paragraph of the SCIM [online documentation](https://docs.wpo365.com/article/59-wordpress-user-provisioning-with-azure-ad-scim) for details. [SCIM, INTRANET]
* Improvement: The (Microsoft Graph) based **Employee Directory shortcode app** has been updated and can now be configured to use **SharePoint People Search** to **search for users by skills, projects** and other profile attributes. It will also **include profile details** on the user's *Info* tab (when using the Contacts template and if the user has updated his / her profile). See the [online documentation](https://docs.wpo365.com/article/111-employee-directory-and-contacts) for details. [M365 APPS, INTRANET]
* Improvement: The (Microsoft Graph) based **Employee Directory shortcode app** also can now be used by users that did not sign in with Microsoft. See the [online documentation](https://docs.wpo365.com/article/111-employee-directory-and-contacts) for details. [M365 APPS, INTRANET]
* Improvement: The **SharePoint / OneDrive Documents shortcode app** (to embed a SharePoint library in WordPress) is **no longer considered deprecated** and has instead been updated and can now also be configured to **allow access to anonymous users** and to retrieve and **render custom SharePoint columns** / fields. See the [online documentation](https://docs.wpo365.com/article/126-sharepoint-library-for-wordpress) for details. [M365 APPS, INTRANET]
* Improvement: The **SharePoint / OneDrive Documents shortcode app** also can now be configured to read its configuration (SharePoint site collection, title of the library and optionally a folder path) from a number of querystring parameters. See the [online documentation](https://docs.wpo365.com/article/126-sharepoint-library-for-wordpress) for details. [M365 APPS, INTRANET]
* Improvement: Also the **Documents Gutenberg Block** has been updated and can now be configured to read its configuration (SharePoint site collection, title of the library and optionally a folder path) from a number of querystring parameters. See the [online documentation](https://docs.wpo365.com/article/128-embedded-sharepoint-library-powerful-gutenberg-blocks-for-wordpress) for details. [M365 APPS, INTRANET]
* Improvement: The **Microsoft Graph mailer for WordPress** can now **log all outgoing WordPress transactional emails** and allows you to **try to re-send emails** that previously failed to send. See the [online documentation](https://docs.wpo365.com/article/108-sending-wordpress-emails-using-microsoft-graph) for details. [MAIL, SYNC, INTRANET]
* Improvement: The **Microsoft Graph mailer for WordPress** now supports sending of **attachments**. See the [online documentation](https://docs.wpo365.com/article/108-sending-wordpress-emails-using-microsoft-graph) for details. [MAIL, SYNC, INTRANET]
* Improvement: The **Microsoft Graph mailer for WordPress** can now be configured to send all outgoing transactional emails as BCC to help you prevent reply-to-all mail polution. See the [online documentation](https://docs.wpo365.com/article/108-sending-wordpress-emails-using-microsoft-graph) for details. [MAIL, SYNC, INTRANET]
* Improvement: On the plugin's *User sync* configuration page in the section *Custom user fields* you can now select how you would like the WPO365 plugin to update a **WordPress user's display name**. Possible choices are: Same as the display name according to Azure AD, given name and surname according to Azure AD or surname, given name according to Azure AD. [CUSTOM USER FIELDS, SYNC, INTRANET]
* Improvement: The plugin will now always apply the Azure AD user attribute to WordPress user meta mappings regardless of whether you have opted to show them on a WordPress user profile page. [CUSTOM USER FIELDS, SYNC, INTRANET]
* Improvement: A new endpoint have been added to the WPO365 REST API for Microsoft Graph where developers can send a preformatted Microsoft Graph request which will be transparantly proxied to Microsoft Graph in the context of the current user. This endpoint must be separately enabled. [ALL]
* Improvement: Another endpoint have been added to the WPO365 REST API for Microsoft Graph where developers can request an oauth access token for Microsoft Graph in the context of the current user. This endpoint must be separately enabled. [ALL]
* Improvement: In accordance with the WordPress developer guidelines direct use of **PHP cURL has been removed** and instead the plugin now uses WordPress's builtin HTTP API. [ALL]
* Fix: The OneLogin library (used for adding SAML support to the plugin) has been updated to the latest version. Small modifications have been made to further ensure compatibility with PHP 8. [ALL]
* Fix: In accordance with the WordPress developer guidelines all output has been secured / escaped. See the [official WordPress documentation](https://developer.wordpress.org/plugins/security/securing-output/) for details. [ALL]
* Fix: A bug has been fixed that prevent the **Internet Auth.-only** Authentication Scenario from working correctly. [LOGIN+, SYNC, INTRANET]
* Fix: A bug has been fixed that prevented the WordPress Users page from loading correctly when using WPO365 Audiences. [ROLES + ACCESS, SYNC, INTRANET]
* Fix: A bug has been fixed that prevented the WPO365 Wizard / Configuration pages to handle non-latin characters. [ALL]
* Fix: Version bump for all WPO365 plugins.

= Older versions =

Please check the [online change log](https://www.wpo365.com/change-log/) for previous changelogs.
