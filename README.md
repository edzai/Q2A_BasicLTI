# Q2A_BasicLTI
The LTI Authentication plugin allows for Q2A to handle authentication through LTI (Learning Tools Interoperability).

The LTI Authentication plugin allows for Question2Answer to handle authentication through LTI (Learning Tools Interoperability).
Author Antoni Bertran Bellido

Installation
============
1) unzip in qa-plugin folder
2) Log as administrator, go to Administration center - Plugins
3) Click over "options"
4) Configure BasicLTI Settings:

- Enable Plugin
- Disable Default BLTI Username: You have to define the parameter of basic lti with the custom username instead of the default BasicLTI Parameter
- Parameter Username: If you enabled Disable BLTI Username you have to indicate the custom username, usuarlly is custom_username
- Logout URL: Full url (include http://) to logout from the SSO and this Q2A application
- New user message: Optional. Message shown to new users when they login for the first time. 
Can include arguments: %1$sÑfirst name; %2$sÑfull name; %3$sÑassigned handle within Question 2 Answer. Note that the assigned handle will be different from the handle in your SSO application if someone else already had that handle within this Q2A system.
Example:
Welcome to the Question & Answer site!<p>You have been assigned user name %3$s. You can change it by clicking on "My Account" on the upper right corner of the screen.</p>
- Welcome message: Optional. Message shown to users when they login other than the first time.
Can include same arguments as the New user message.

Example:
Welcome back %1$s!
Settings
========
Full description:
BasicLTI provides a mechanism to launch an external applications from Learning Management System. You can read more information here

http://www.imsglobal.org/lti/blti/bltiv1p0/ltiBLTIimgv1p0.html

This plugin is a provider tool, is based on UOC code. 

The plugin has a configuration file:

plugins/LTIAuthentication/IMSBasicLTI/configuration/authorizedConsumersKey.cfg you can define the user and password, for example to define the consumer key "external" yo have to  define consumer_key.external.enabled=1 consumer_key.external.secret=pwd_12345
