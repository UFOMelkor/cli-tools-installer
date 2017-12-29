<?php
declare(strict_types=1);

namespace UFOMelkor\CliTools\Archive;

use Alchemy\Zippy\Zippy;
use Github\Client;

final class ReleaseFetcher
{
    /** @var Client */
    private $github;

    /** @var Zippy */
    private $zippy;

    public function __construct(Client $github, Zippy $zippy)
    {
        $this->github = $github;
        $this->zippy = $zippy;
    }

    /** @return Release */
    public function fetchLatestRelease(string $owner, string $repository): Release
    {
        $latest = $this->github->repository()->releases()->latest($owner, $repository);
        return new Release($this->zippy, $latest);
    }
}
