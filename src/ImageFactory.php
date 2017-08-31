<?php

namespace Tsuka\Image;

use Katana\Sdk\File;

class ImageFactory
{
    /**
     * @param File $file
     * @return Image
     */
    public function build(File $file): Image
    {
        if ($file->isLocal()) {
            $filePath = $file->getPath();
        } else {
            $filePath = tempnam(sys_get_temp_dir(), 'img_');
            file_put_contents($filePath, $file->read());
        }

        $imagick = new \Imagick($filePath);

        return new Image($imagick, $file);
    }
}
