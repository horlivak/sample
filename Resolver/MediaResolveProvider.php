<?php

declare(strict_types=1);


namespace App\Service\Media\Resolver;


use App\Service\Media\DTO\File;
use App\Service\Media\Entity\MediaInterface;

class MediaResolveProvider
{
    /**
     * @var ResolverInterface[]
     */
    private $mediaResolvers;

    private $supportedResolvers;

    public function __construct(array $resolvers)
    {
        $this->mediaResolvers = $resolvers;
    }

    public function upload(File $file): MediaInterface
    {
        return $this->getResolver($file)->upload($file);
    }

    public function supportUpload(File $file): bool
    {
        try {
            $this->getResolver($file);
        } catch (\RuntimeException $exception) {
            return false;
        }
        return true;
    }

    private function getResolver(File $file): ResolverInterface
    {
        if (isset($this->supportedResolvers[$file->getType()])) {
            return $this->supportedResolvers[$file->getType()];
        }

        foreach ($this->mediaResolvers as $key => $resolver) {
            if ($resolver->supportUpload($file)) {
                $this->supportedResolvers[$file->getType()] = $resolver;
                return $resolver;
            }
        }

        throw new \RuntimeException(sprintf('No resolver found for file type "%s".', $file->getType()));
    }
}