<?php
declare(strict_types=1);

namespace UFOMelkor\CliTools\Archive;

use Alchemy\Zippy\Zippy;
use RuntimeException;
use function array_column;
use function unlink;

final class Release
{
    /** @var Zippy */
    private $extractor;

    /** @var array */
    private $data;

    public function __construct(Zippy $extractor, array $data)
    {
        $this->extractor = $extractor;
        $this->data = $data;
    }

    /** @return string[] */
    public function assetLabels(): array
    {
        return array_column($this->data['assets'], 'label');
    }

    public function fetchAsset(string $label): TgzAsset
    {
        $asset = array_reduce($this->data['assets'], function ($prev, $curr) use ($label) {
            return $curr['label'] === $label ? $curr : $prev;
        });
        if (! $asset) {
            throw new RuntimeException("Unknown asset: ${label}");
        }
        $tmpDir = sys_get_temp_dir() . '/' . uniqid('php_cli_tools_', true);
        mkdir($tmpDir);
        copy($asset['browser_download_url'], "$tmpDir/download.tar.gz");
        $zipOpen = $this->extractor->open("$tmpDir/download.tar.gz");
        $zipOpen->extract($tmpDir);
        unlink("$tmpDir/download.tar.gz");
        return new TgzAsset($tmpDir);
    }
}
