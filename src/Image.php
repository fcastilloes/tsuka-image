<?php

namespace Tsuka\Image;

use Imagick;
use Katana\Sdk\File;

class Image
{
    /**
     * @var Imagick
     */
    private $imagick;

    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $units = [];

    /**
     * @param Imagick $imagick
     * @param File $file
     */
    public function __construct(Imagick $imagick, File $file)
    {
        $this->imagick = $imagick;
        $this->file = $file;
    }

    public function getExtension(): string
    {
        return substr(
            strstr($this->file->getFilename(), '.'),
            1
        );
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->imagick->getImageWidth();
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->imagick->getImageHeight();
    }

    /**
     * @return bool
     */
    public function isAnimation(): bool
    {
        return $this->imagick->getNumberImages() > 1;
    }

    /**
     * @param int $width
     * @param int $height
     */
    private function cropUnit(int $width, int $height): void
    {
        $this->imagick->cropImage($width, $height, 0, 0);
        $this->imagick->setImagePage($width, $height, 0, 0);
    }

    /**
     * @param int $width
     * @param int $height
     */
    private function resizeUnit(int $width, int $height): void
    {
        $this->imagick->resizeImage($width, $height, Imagick::FILTER_BOX, 1);
    }

    /**
     * @param int $size
     * @return string
     * @throws \ImagickException
     */
    public function resizeCropSquare(int $size): string
    {
        $cropSize = 0;
        if ($this->getWidth() !== $this->getHeight()) {
            $cropSize = min($this->getWidth(), $this->getHeight());
            $this->addUnit([$this, 'cropUnit'], $cropSize, $cropSize);
        }

        if ($cropSize !== $size) {
            if ($this->getWidth() !== $size || $this->getHeight() !== $size) {
                $this->addUnit([$this, 'resizeUnit'], $size, $size);
            }
        }

        $this->execute();

        return str_replace(
            "{$this->getExtension()}:",
            '',
            $this->imagick->getImageFilename()
        );
    }

    /**
     * @param callable $func
     * @param array $args
     */
    private function addUnit(callable $func, ...$args): void
    {
        $this->units[] = [
            'func' => $func,
            'args' => $args,
        ];
    }

    private function execute()
    {
        $image = "{$this->getExtension()}:{$this->imagick->getImageFilename()}";
        if ($this->isAnimation()) {
            $this->executeAnimationUnits();
            $this->imagick->writeImages($image, true);
        } else {
            $this->executeUnits();
            $this->imagick->writeImage($image);
        }

        $this->units = [];
    }

    private function executeAnimationUnits(): void
    {
        $this->imagick = $this->imagick->coalesceImages();

        do {
            $this->executeUnits();
        } while ($this->imagick->nextImage());

        $this->imagick = $this->imagick->deconstructImages();
    }

    private function executeUnits(): void
    {
        foreach ($this->units as ['func' => $func, 'args' => $args]) {
            call_user_func_array($func, $args);
        }
    }

    function __destruct()
    {
        if (!$this->file->isLocal()) {
            unlink($this->imagick->getImageFilename());
        }
    }
}
