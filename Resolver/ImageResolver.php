<?php

declare(strict_types=1);


namespace App\Service\Media\Resolver;


use App\Entity\Media;
use App\Service\Media\Entity\MediaInterface;
use App\Service\Media\DTO\File;
use App\Service\Media\Exception\InvalidMediaException;
use App\Service\Media\FileSystem\TemporaryFileFactory;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Service\FilterService;

class ImageResolver extends AbstractResolver
{
    private $imageMimeTypes = ['image/jpeg', 'image/gif', 'image/bmp', 'image/png'];
    /**
     * @var TemporaryFileFactory
     */
    private $temporaryFileFactory;
    /**
     * @var string
     */
    private $mediaPath;
    /**
     * @var FilterManager
     */
    private $filterManager;
    /**
     * @var FilterService
     */
    private $filterService;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(TemporaryFileFactory $temporaryFileFactory,
                                FilterManager $filterManager,
                                FilterService $filterService,
                                EntityManagerInterface $entityManager,
                                string $mediaPath,
                                array $imageMimeTypes = [])
    {
        $this->temporaryFileFactory = $temporaryFileFactory;
        $this->mediaPath = $mediaPath;
        $this->filterManager = $filterManager;
        $this->filterService = $filterService;
        if (!empty($imageMimeTypes)) {
            $this->imageMimeTypes = $imageMimeTypes;
        }
        $this->entityManager = $entityManager;
    }

    /**
     * @param File $file
     * @return MediaInterface
     * @throws InvalidMediaException
     */
    public function upload(File $file): MediaInterface
    {
        $media = $this->createFile($file);

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    public function supportUpload(File $file): bool
    {
        return $file->getType() === 'image';
    }

    protected function isImage(string $mime): bool
    {
        return in_array($mime, $this->imageMimeTypes);
    }

    /**
     * @param File $file
     * @return MediaInterface
     * @throws InvalidMediaException
     */
    protected function createFile(File $file): MediaInterface
    {
        if($file->getData() === null) {
            throw new InvalidMediaException("Empty data file.");
        }

        if(!$this->isImage($file->getMime())) {
            throw new InvalidMediaException("Given mime type '{$file->getMime()}' is not image.");
        }

        $temporaryFile = $this->temporaryFileFactory->create($file->asDataBinary(), "media_image_manager");
        $mimeType = $temporaryFile->getMimeType();
        if(!self::isImage($mimeType)) {
            throw new InvalidMediaException("Checked mime type '$mimeType' is not image.");
        }

        $originalFile = $this->processMediaFile($temporaryFile, $this->mediaPath, date("Y/m/d"));

        $requiredFilters = [];

        if ($file->getSize() !== null || !empty($file->getSize())) {
            foreach ($file->getSize() as $size)
            {
                $requiredFilters["{$file->getModule()}.$size"] = "{$file->getModule()}.$size";
            }

            $filters = array_intersect(array_keys((array) $this->filterManager->getFilterConfiguration()->all()), $requiredFilters);
        } else {
            $filters = array_keys((array) $this->filterManager->getFilterConfiguration()->all());
            $moduleFilters = [];
            foreach ($filters as $filter) {
                str_contains($filter, "{$file->getModule()}.") ? $moduleFilters[] = $filter : null;
            }
            $filters = $moduleFilters;
        }

        if (empty($filters)) {
            throw new InvalidMediaException("Filters for module `{$file->getModule()}` doesnt found");
        }

        $originalName = $originalFile->getFilename();
        $relativeMediaPath = date("Y/m/d");

        foreach ($filters as $filter) {
            $this->filterService->getUrlOfFilteredImage("$relativeMediaPath/$originalName", $filter);
        }

        $media = new Media();
        $media->setHash($originalFile->getFilename());
        $media->setType($file->getType());
        $media->setMimeType($originalFile->getMimeType());
        $media->setOriginalPath($originalFile->getPath());
        $media->setRelativePath($relativeMediaPath);
        $media->setModule($file->getModule());

        return $media;
    }
}