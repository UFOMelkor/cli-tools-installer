<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use Exception;

class HumanInteractionNeeded extends Exception
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}