#!/bin/bash
TOP_LEVEL=$(git rev-parse --show-toplevel)

if [[ ! -z "$1" ]]
then
  PATHS=$1
else
  PATHS=$(git diff --name-only --cached | tr -u '\n' ' ')
  if [[ -z "$PATHS" ]]
  then
    PATHS=$TOP_LEVEL
  fi
fi

# confirm if there are no $PATHS is toplevel
if [[ "$PATHS" == "$TOP_LEVEL" ]]; then
  read -r -p "No specific files identified, are you sure you want to reformat all files in the repo? [y/N] " response
  case "$response" in
      [yY][eE][sS]|[yY])
          ./vendor/bin/php-cs-fixer fix --config doc/CodeStyle/php-cs-fixer-config.php --using-cache=no
          ;;
      *)
          echo "aborted..."
          ;;
  esac
  else
    ./vendor/bin/php-cs-fixer fix --config doc/CodeStyle/php-cs-fixer-config.php --using-cache=no $PATHS
fi

