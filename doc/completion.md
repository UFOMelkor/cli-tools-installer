---
currentMenu: completion
---
# Bash Completion

## Composer

Installed via `cli-install bash:completion:composer` or `cli-install b:c:c`
Using composer from the command line is cool but looking into
https://packagist.org/ every time you forgot how a package is spelled is not.
 
iArren developed a bash completion that will complete both package name and
version.

If you want to know more please visit
https://github.com/iArren/composer-bash-completion
 
## Tools based on Symfony Console

Installed via `cli-install bash:completion:symfony-console` or `cli-install b:c:s`

Many command line tools in PHP (like Behat, php-cs-fixer, phpmetrics, PHPSpec
and every Symfony Application) are based on the awesome Symfony Console.
Therefore it is really useful to have autocompletion for the Symfony Console.
 
Fortunately Bilal Amarni developed a tool that provides a basic completion for
tools based on Symfony Console. If you want to know more have a look at
https://github.com/bamarni/symfony-console-autocomplete
 
Although composer is also based on the Symfony Console, I do not recommend to
enable this tool for composer, because there is another tool especially
developed for composer that provides a better completion for composer (see
above).
