<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig_Environment;
use UFOMelkor\CliTools\Config\Config;

class InstallSymfonyShortcuts extends Command
{
    /** @var Config */
    private $config;

    /** @var Twig_Environment */
    private $twig;

    public function __construct(Config $config, Twig_Environment $twig)
    {
        parent::__construct('symfony:console:shortcuts');
        $this->setDescription('Shortcuts for accessing the Symfony console');
        $this->config = $config;
        $this->twig = $twig;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $console = new Output($input, $output, $this->getHelper('question'));
        $binPath = $this->config->getBinDirectory($console);
        $forceAnsi = $this->config->isForcingAnsi($console);

        $environments = ['dev', 'prod'];
        foreach ($environments as $env) {
            $target = "$binPath/$env";

            $doInstall = $console->confirm(
                "Do you want to install a Symfony console shortcut for the $env environment?"
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
                    $console->updateNotNecessary("$target is already the latest version");
                    continue;
                }
                if (@file_put_contents($target, $script) === false) {
                    $console->error("Could not update the Symfony console shortcut for the $env environment.");
                    return 1;
                }
                $console->updateSuccess("Updated the Symfony console shortcut for the $env environment to $target");
                continue;
            }
            if (@file_put_contents($target, $script) === false) {
                $console->error("Could not install the Symfony console shortcut for the $env environment.");
                return 1;
            }
            $console->installationSuccess("Successfully installed the shortcut for the $env environment to $target");
        }
        return 0;
    }
}
