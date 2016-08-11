<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig_Environment;
use UFOMelkor\CliTools\Config\Config;

class InstallPhpSpecConsoleShortcuts extends Command
{
    /** @var Config */
    private $config;

    /** @var Twig_Environment */
    private $twig;

    public function __construct(Config $config, Twig_Environment $twig)
    {
        parent::__construct('phpspec:shortcuts');
        $this->setDescription('Shortcuts for phpspec');
        $this->setHelp(
            <<<HELP
phpspec is a great tool but it is intensively using the command line. This
might scare of developers that are not using the command line normally.
Therefore two scripts are provided that will help using phpspec from command
line.

phpspec provides two commands and so there are two scripts. The first is called
<options=underscore>describe</>. It is a shortcut for <options=underscore>vendor/bin/phpspec describe</>.
But it will do more. If you have a composer.json and you have only one PSR-4
mapping configured in your autoload section, then you can omit the part of the
class that you describe. Assuming that you configured <options=bold>Acme\\Foo\\ </>in your
composer.json, you can execute <options=underscore>describe Bar/Baz</> instead of
<options=underscore>vendor/bin/phpspec describe Acme/Foo/Bar/Baz</>.

The other script does less magic. Its called <options=underscore>pspec</> and is a shortcut for
<options=underscore>vendor/bin/phpspec run</>.  
HELP
        );
        $this->config = $config;
        $this->twig = $twig;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('phpspec Shortcuts');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }
        $binPath = $this->config->getBinDirectory($io);
        $forceAnsi = $this->config->isForcingAnsi($io);

        $shortcuts = [
            'describe' => 'phpspec-describe-shortcut.php.twig',
            'pspec' => 'phpspec-run-shortcut.sh.twig',
        ];
        foreach ($shortcuts as $name => $template) {
            $io->section("<options=underscore>$name</> shortcut");
            if ($io->confirm("Do you want to install the <options=underscore>$name</> shortcut?")) {
                $target = "$binPath/$name";
                $script = $this->twig->render($template, ['ansi' => $forceAnsi]);
                if (file_exists($target)) {
                    if (file_get_contents($target) === $script) {
                        $io->text("$target is already the latest version.");
                        continue;
                    }
                    if (@file_put_contents($target, $script) === false) {
                        $io->error("Could not write to $target.");
                        return 1;
                    }
                    $io->success("Updated the $name shortcut for phpspec ($target).");
                    continue;
                }
                if (@file_put_contents($target, $script) === false) {
                    $io->error("Could not write to $target.");
                    return 1;
                }
                chmod($target, 0755);
                $io->success("Installed the $name shortcut for phpspec to $target.");
            }
        }
        return 0;
    }
}
