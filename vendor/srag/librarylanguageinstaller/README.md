# srag/librarylanguageinstaller Library for ILIAS Plugins

Install additional and external lang files for a plugin

This project is licensed under the GPL-3.0-only license

## Usage

### Composer

First add the following to your `composer.json` file:

```json
"require": {
  "srag/librarylanguageinstaller": ">=1.0.0"
},
```

And run a `composer install`.

If you deliver your plugin, the plugin has it's own copy of this library and the user doesn't need to install the library.

Tip: Because of multiple autoloaders of plugins, it could be, that different versions of this library exists and suddenly your plugin use an older or a newer version of an other plugin!

So I recommand to use [srag/librariesnamespacechanger](https://packagist.org/packages/srag/librariesnamespacechanger) in your plugin.

### LibraryLanguageInstaller

Expand you plugin class for installing languages of a library to your plugin

```php
...
use srag\LibraryLanguageInstaller\OpencastObject\x\LibraryLanguageInstaller;
...
	/**
     * @inheritDoc
     */
    public function updateLanguages(/*?array*/ $a_lang_keys = null) : void {
		parent::updateLanguages($a_lang_keys);

		LibraryLanguageInstaller::getInstance()->withPlugin(self::plugin())->withLibraryLanguageDirectory(__DIR__ . "/../vendor/srag/x/lang")
			->updateLanguages($a_lang_keys);
	}
...
```

## Requirements

* ILIAS 6.0 - 7.999
* PHP >=7.2
