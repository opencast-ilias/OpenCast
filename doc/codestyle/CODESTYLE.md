# Code-Style

In general, we use the same Code-Style as the ILIAS-Project (see [README](https://github.com/ILIAS-eLearning/ILIAS/blob/trunk/docs/development/coding-style.md)) which is PSR-12.

While developing you can use php-cs-fixer with the configuration here: [php-cs-fixer-config.php](php-cs-fixer-config.php)

```bash
./vendor/bin/php-cs-fixer fix --config doc/CodeStyle/php-cs-fixer-config.php [OPTIONAL_PATH_TO_CODE]
```

You can use the following script to re-format code changes staged in git. If there are no staged files present, all files will be re-formatted:

```bash
./doc/codestyle/run-code-format.sh
```
