# DBUpdate

After each update, the plugin checks if the PluginConfig is empty, and if so, installs the [default_config.xml](../configuration/default_config.xml).
This is to define a default configuration when first installing the plugin. See ilOpenCastPlugin::afterUpdate

Unfortunately, this means you should not write to PluginConfig in the dbupdate.php. Or only with an if-clause: if (PluginConfig::count() > 0).

Ideally we should overthink this, so that default configs can be set in the dbupdate, while still being able to import the
default_config.xml when installing the plugin (maybe use ilPlugin::afterInstall?).