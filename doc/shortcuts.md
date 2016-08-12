---
currentMenu: shortcuts
---
# Shortcuts

## Symfony Console

Installed via `cli-install shortcuts:symfony-console` or `cli-install s:s`

Typing `bin/console --env=dev cache:clear` is long and not really practicable.
Symfony allows to use abbreviations for the commands like
`bin/console --env=dev c:c`, but this is also long.

The PHP CLI Tools install shortcuts for the **`dev`** and **`prod`** environments that will
make you able to use `dev c:c instead` of `bin/console --env=dev c:c` and
`prod c:c` instead of `bin/console --env=prod --no-debug c:c`.
 
They will work for both the `bin/console` of Symfony3 and the `app/console` of
Symfony2.


## PHPSpec

Installed via `cli-install shortcuts:phpspec` or `cli-install s:p`

phpspec is a great tool but it is intensively using the command line. This
might scare of developers that are not using the command line normally.
Therefore two scripts are provided that will help using phpspec from command
line.
 
phpspec provides two commands and so there are two scripts. The first is called
**`describe`**. It is a shortcut for `vendor/bin/phpspec describe`.
But it will do more. If you have a `composer.json` and you have only one PSR-4
mapping configured in your autoload section, then you can omit the part of the
class that you describe. Assuming that you configured `Acme\Foo\` in your
`composer.json`, you can execute `describe Bar/Baz` instead of
`vendor/bin/phpspec describe Acme/Foo/Bar/Baz`.
 
The other script does less magic. Its called **`pspec`** and is a shortcut for
`vendor/bin/phpspec` run.  