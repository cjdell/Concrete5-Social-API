Concrete5-Social-API
====================

Add sign in and message posting capabilities for both Twitter and Facebook to Concrete5 (for developers)

Installation
------------

- Clone into your C5 site "packages" folder
- Rename the cloned folder to "social_api"
- Install package within C5 Dashboard
- Copy sample config files in /libraries/social/config to the files "twconfig.php" and "fbconfig.php" in the same folder
- Insert the necessary API keys within above config files

Testing
-------
- Log out of C5
- Navigate to /social_api
- Attempt to sign in with a Twitter/Facebook account
- If sign in is successful, attempt to post a message
- Open file "single_pages/social_api/view.php" to see usage of the libraries
