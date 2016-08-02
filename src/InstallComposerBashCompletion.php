<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Exception;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UFOMelkor\CliTools\Config\Config;

class InstallComposerBashCompletion extends Command
{
    const SOURCE = 'https://raw.githubusercontent.com/iArren/composer-bash-completion/master/composer';

    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct('composer:completion:bash');
        $this->setDescription('Bash completion for composer: https://github.com/iArren/composer-bash-completion');
        $this->config = $config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $console = new Output($input, $output, $this->getHelper('question'));

        $target = $this->config->getBashCompletionDirectory($console) . '/composer';

        try {
            $result = (string) (new Client())->get(self::SOURCE)->getBody();
        } catch (Exception $exception) {
            $console->error('Could not grab the completion file from ' . self::SOURCE);
            return 1;
        }
        if (! @file_put_contents($target, $result)) {
            $console->error("Could not write to $target. Do you need sudo permissions?");
            return 1;
        }
        $output->writeln('Successfully installed the lastest version of iArren\'s composer bash completion');
        return 0;
    }
}
