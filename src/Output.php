<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Output
{
    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /** @var QuestionHelper */
    private $helper;

    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $this->input = $input;
        $this->output = $output;
        $this->helper = $helper;
    }

    public function confirm($text, $defaultTrue = true): bool
    {
        $choices = $defaultTrue ? '[Yn]' : '[yN]';
        return (bool) $this->helper->ask($this->input, $this->output, new ConfirmationQuestion(
            "<question>$text $choices</question> ",
            $defaultTrue
        ));
    }

    public function choose($text, array $choices, string $default): string
    {
        return $this->helper->ask($this->input, $this->output, new ChoiceQuestion(
            "<question>$text [" . implode('/', $choices) . "] ($default)</question> ",
            $choices,
            $default
        ));
    }

    public function ask($text, string $default)
    {
        return $this->helper->ask($this->input, $this->output, new Question(
            "<question>$text ($default)</question> ",
            $default
        ));
    }

    public function error(string $text)
    {
        $this->output->writeln("<error>$text</error>");

    }

    public function updateNotNecessary(string $text)
    {
        $this->output->writeln($text, OutputInterface::VERBOSITY_VERBOSE);
    }

    public function updateSuccess(string $text)
    {
        $this->output->writeln("<info>$text</info>");
    }

    public function installationSuccess(string $text)
    {
        $this->output->writeln("<info>$text</info>");
    }

    public function writeln(string $line, int $option = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->output->writeln($line, $option);
    }

    public function isInteractive(): bool
    {
        return $this->input->isInteractive();
    }
}