<?php

namespace UnzerPayment6\Components\BackwardsCompatibility;

class Filesystem
{
    /**
     * @return null|bool Before Shopware 6.5.0.0, both update and write methods have returned bool, now the write method returns void and the update method doesn't exist anymore.
     */
    public static function put(\League\Flysystem\Filesystem $filesystem, string $path, string $content)
    {
        // TODO: Remove me if compatibility is at least 6.5.0.0
        if (method_exists($filesystem, 'update') && $filesystem->has($path)) {
            return $filesystem->update($path, $content);
        }

        return $filesystem->write($path, $content);
    }

}