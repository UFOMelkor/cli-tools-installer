# PHP CLI Tools

There are still many PHP developers that do not use their console because it
takes longer to do as if they would do it with their IDE.

While this might be true for some tasks there are other tasks that can be
accomplished faster within your CLI or that can be accomplished only within
your CLI (like the Symfony Console).

E.g. PHPStorm provides a special *Command Line Tools Console* that provides
autocompletion for Symfony Console tools. Although this feature might be quite
nice, the *Command Line Tools Console* is not as powerful as e.g. the bash.

A better approach would be to pimp your console with various tools that make
working with the bash more comfortable. And while there are many tools that
do a great job, they are scattered and installed by several ways.

The **PHP CLI Tools** are an installer for various CLI tools.
Most of the tools are specific to PHP but some will also help you dealing with
Git in the command line or something like this.

You can install them by running
**`composer global require ufomelkor/php-cli-tools`** and then `cli-install`
to have a look at the available tools (assuming that you have the global
bin-dir of composer in your path). If you want to install all tools simple run
**`cli-install all`**.
