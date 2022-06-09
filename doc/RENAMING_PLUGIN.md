# Renaming the Plugin

The ILIAS-Opencast-Plugin Community decided to rename this plugin to provide a clear distinction between this plugin and the new [Opencast Event plugin](https://github.com/opencast-ilias/OpencastEvent). Another reason for the renaming was to fix the previously used wrong spelling of Opencast.

## Rename the Plugin before updating it
You won't be able to update the PLugin until the following steps has been performed. Unfortunately ILIAS does not offer a simple way to rename a Plugin (or support the Renaming). You must perform the following Steps manually on you Server:

1. Rename Plugin in Database
Please perform a renaming of the Plugin Entry in the table `il_plugin`: `UPDATE il_plugin SET name = 'OpencastObject' WHERE plugin_id = 'xoct';`

2. Rename Directory
Rename the directory `Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast` to `Customizing/global/plugins/Services/Repository/RepositoryObject/OpencastObject`. Check if you have references to the old directories e.g. in Git-Submodules.

3. Performing Composer Dump-Autoload
At least ILIAS 7 parses Plugin directories as well during a composer install/update/dump-autoload. Therefore, references to the old directory-name still exist in the Composer Classmap. Please perform a `composer dump-autoload` in the root directory of ILIAS after you have renamed the plugin directory.
