<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class InstallSymfonyShortcuts extends Command
{
    public function __construct()
    {
        parent::__construct('symfony:console:shortcuts');
        $this->setDescription('Shortcuts for accessing the symfony console');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $question = $this->getHelper('question'); /* @var $question \Symfony\Component\Console\Helper\QuestionHelper */

        $environments = ['dev', 'prod'];
        foreach ($environments as $env) {
            $target = getenv('HOME') . '/bin/' . $env;
            $source = realpath(__DIR__ . '/../bin/symfony-' . $env);

            $doInstall = $question->ask($input, $output, new ConfirmationQuestion(
                "<question>Do you want to install a shortcut for the $env environment? [y]</question> "
            ));
            if (! $doInstall) {
                continue;
            }
            if (file_exists($target)) {
                if (is_link($target) && realpath(readlink($target)) === $source) {
                    $output->writeln("Shortcut for the $env environment already exists");
                    continue;
                }
                $output->writeln(
                    "<error>Could not install a shortcut for the $env environment because $target "
                    . "already exists</error>"
                );
                return 1;
            }
            symlink($source, $target);
            $output->writeln(
                "<info>Successfully installed the shortcut for the $env environment to $target</info>"
            );
        }
    }
}
