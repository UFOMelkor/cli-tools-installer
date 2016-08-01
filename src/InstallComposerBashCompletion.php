<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Exception;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InstallComposerBashCompletion extends Command
{
    const SOURCE = 'https://raw.githubusercontent.com/iArren/composer-bash-completion/master/composer';

    public function __construct()
    {
        parent::__construct('composer:completion:bash');
        $this->setDescription('Bash completion for composer: https://github.com/iArren/composer-bash-completion');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $question = $this->getHelper('question'); /* @var $question \Symfony\Component\Console\Helper\QuestionHelper */
        $target = $question->ask($input, $output, new Question(
                '<question>Where to put the completion file? [/usr/share/bash-completion/completions/composer]</question>',
                '/usr/share/bash-completion/completions/composer'
        ));

        try {
            $result = (string) (new Client())->get(self::SOURCE)->getBody();
        } catch (Exception $exception) {
            $output->writeln('<error>Could not grab the completion file from ' . self::SOURCE . "</error>");
            return 1;
        }
        if (! @file_put_contents($target, $result)) {
            $output->writeln("<error>Could not write to $target. Do you need sudo permissions?</error>");
            return 1;
        }

    }
}
