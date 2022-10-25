##Important notes for migration

In order to be able to update/migrate to versions 5.x and higher of this ILIAS plugin for Opencast there are certain preconditions which must be fulfilled:

The current database version of your plugin is 36 or lower.
You can check the current database version of your installation on the information-page of the plugin under “Current Version”.
(“Administration”->”Extending ILIAS”-> “Plugins” click on  the “Actions” Dropdown for OpenCast“ and choose “Information”)

A migration/update is not possible after the database version of your plugin is 37 or higher.

**Migration back to other plugin versions is not possible**

A migration back to the other existing ILIAS plugin for opencast (i.e., https://github.com/fluxapps/OpenCast) is not possible after the DB-Update step 37 has been executed.


##Steps for migration

Please exchange NEW_REMOTE with whatever name you would like to use for the new remote repository.

1. Add the new remote in your Opencast-Directory
```bash
        git remote add NEW_REMOTE https://github.com/opencast-ilias/OpenCast.git
```
2. Fetch the new branch 
```bash
        git fetch NEW_REMOTE
```
3. Checkout the main-branch
```bash
        git checkout NEW_REMOTE/main
```
4. You can then delete the old repository from your server.  
```bash
        git remote remove OLD_REPO_NAME
```
If you do not know the name of your old repo, you can check with  
```bash
       git remote -v 
```