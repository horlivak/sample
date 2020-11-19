<?php


namespace App\Service\Media\Resolver;


use App\Service\Media\DTO\File;
use App\Service\Media\Entity\MediaInterface;

interface ResolverInterface
{
    public function upload(File $file): MediaInterface;

    public function supportUpload(File $file): bool;
}