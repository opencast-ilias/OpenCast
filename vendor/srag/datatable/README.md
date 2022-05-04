# srag/datatable Library for ILIAS Plugins

ILIAS Data Table UI Component

This project is licensed under the GPL-3.0-only license

## Usage

### Composer

First add the following to your `composer.json` file:

```json
"require": {
  "srag/datatable": ">=0.1.0"
},
```

And run a `composer install`.

If you deliver your plugin, the plugin has it's own copy of this library and the user doesn't need to install the library.

Tip: Because of multiple autoloaders of plugins, it could be, that different versions of this library exists and suddenly your plugin use an older or a newer version of an other plugin!

So I recommand to use [srag/librariesnamespacechanger](https://packagist.org/packages/srag/librariesnamespacechanger) in your plugin.

## Using trait

Your class in this you want to use DataTableUI needs to use the trait `DataTableUITrait`

```php
...
use srag\DataTableUI\OpencastObject\x\Implementation\Utils\DataTableUITrait;
...
class x {
...
use DataTableUITrait;
...
```

You can also use `AbstractTableBuilder` for build your table

## Languages

Expand you plugin class for installing languages of the library to your plugin

```php
...
	/**
     * @inheritDoc
     */
    public function updateLanguages(/*?array*/ $a_lang_keys = null) : void {
		parent::updateLanguages($a_lang_keys);

		self::dataTableUI()->installLanguages(self::plugin());
	}
...
```

## Use

In your code

```php
...
self::dataTableUI()->table(...)->withPlugin(self::plugin());
...
```

Get selected action row id

```php
$table->getBrowserFormat()->getActionRowId($table->getTableId());
```

Get multiple selected action row ids

```php
$table->getBrowserFormat()->getMultipleActionRowIds($table->getTableId());
```

## Limitations

In ILIAS 5.4 a default container form ui is used for the filter, in ILIAS 6, the new filter ui is used

## Requirements

* ILIAS 6.0 - 7.999
* PHP >=7.2
