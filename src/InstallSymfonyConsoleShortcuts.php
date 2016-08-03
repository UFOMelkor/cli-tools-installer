<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig_Environment;
use UFOMelkor\CliTools\Config\Config;

class InstallSymfonyConsoleShortcuts extends Command
{
    /** @var Config */
    private $config;

    /** @var Twig_Environment */
    private $twig;

    public function __construct(Config $config, Twig_Environment $twig)
    {
        parent::__construct('symfony:console:shortcuts');
        $this->setDescription('Shortcuts for accessing the Symfony Console');
        $this->setHelp(
            <<<HELP
Typing bin/console --env=dev cache:clear is long and not really practicable.
Symfony allows to use abbreviations for the commands like
bin/console --env=dev c:c, but this is also long.
You are going to install shortcuts for the dev and prod environments that will
make you able to use

\tdev c:c\t\t instead of\tbin/console --env=dev c:c
and
\tprod c:c\t instead of\tbin/console --env=prod --no-debug c:c

They will work for both the bin/console of Symfony3 and the app/console of
Symfony2.
HELP
        );
        $this->config = $config;
        $this->twig = $twig;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Symfony Console Shortcuts');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        $globalInstallation = $this->config->isGlobalInstallation($io);
        $binPath = $this->config->getBinDirectory($io);
        $forceAnsi = $this->config->isForcingAnsi($io);

        $environments = ['dev', 'prod'];
        foreach ($environments as $env) {
            $io->section("Shortcut for $env environment");
            $target = "$binPath/$env";

            $doInstall = $io->confirm(
                "Do you want to install a Symfony Console Shortcut for the $env environment?"
            );
            if (! $doInstall) {
                continue;
            }
            $script = $this->twig->render(
                'symfony-console-shortcut.sh.twig',
                ['env' => $env, 'debug' => $env !== 'prod', 'ansi' => $forceAnsi]
            );
            if (file_exists($target)) {
                if (file_get_contents($target) === $script) {
                    $io->text("$target is already the latest version.");
                    continue;
                }
                if (@file_put_contents($target, $script) === false) {
                    $io->error("Could not write to $target.");
                    return 1;
                }
                $io->success("Updated the Symfony Console Shortcut for the $env environment ($target).");
                continue;
            }
            if (@file_put_contents($target, $script) === false) {
                $io->error("Could not write to $target.");
                return 1;
            }
            if ($globalInstallation) {
                chmod($target, 0755);
            }
            $io->success("Installed the Symfony Console Shortcut for the $env environment to $target.");
        }
        return 0;
    }
}
