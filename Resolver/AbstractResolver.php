<?php

declare(strict_types=1);


namespace App\Service\Media\Resolver;


use App\Service\Media\FileSystem\TemporaryFile;
use App\Service\Media\TemporaryMediaFile;
use Symfony\Component\HttpFoundation\File\File;

abstract class AbstractResolver implements ResolverInterface
{
    public function processMediaFile(TemporaryFile $temporaryFile, string $mediaPath, string $relativeMediaPath): File
    {
        $mediaFile = new TemporaryMediaFile($temporaryFile);
        $temporaryFile = new \Symfony\Component\HttpFoundation\File\File($mediaFile->getTmpFile());
        $newMediaPath = "$mediaPath/$relativeMediaPath";

        if(!file_exists($newMediaPath) || !is_dir($newMediaPath)) {
            mkdir($newMediaPath, 0777, true);
        }

        $originalName = uniqid();
        return $temporaryFile->move($newMediaPath, $originalName);
    }
}