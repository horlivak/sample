<?php

declare(strict_types=1);


namespace App\Service\Media\Resolver;


use App\Entity\Media;
use App\Service\Media\DTO\File;
use App\Service\Media\Entity\MediaInterface;
use App\Service\Media\Exception\InvalidMediaException;
use App\Service\Media\FileSystem\TemporaryFileFactory;
use Doctrine\ORM\EntityManagerInterface;

class FileResolver extends AbstractResolver
{
    /**
     * @var TemporaryFileFactory
     */
    private $temporaryFileFactory;
    /**
     * @var string
     */
    private $mediaPath;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(TemporaryFileFactory $temporaryFileFactory, string $mediaPath, EntityManagerInterface $entityManager)
    {
        $this->temporaryFileFactory = $temporaryFileFactory;
        $this->mediaPath = $mediaPath;
        $this->entityManager = $entityManager;
    }

    public function upload(File $file): MediaInterface
    {
        if($file->getData() === null) {
            throw new InvalidMediaException("Empty data file.");
        }
        $temporaryFile = $this->temporaryFileFactory->create($file->asDataBinary(), "media_file_manager_");

        $originalFile = $this->processMediaFile($temporaryFile, $this->mediaPath, date("Y/m/d"));

        $media = new Media();
        $media->setHash($originalFile->getFilename());
        $media->setType($file->getType());
        $media->setMimeType($originalFile->getMimeType());
        $media->setOriginalPath($originalFile->getPath());
        $media->setRelativePath(date("Y/m/d"));
        $media->setModule($file->getModule());

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    public function supportUpload(File $file): bool
    {
        return $file->getType() === 'file';
    }

}