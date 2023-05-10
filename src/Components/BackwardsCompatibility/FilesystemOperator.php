<?php

declare(strict_types=1);

namespace League\Flysystem;

// TODO: Remove me if compatibility is at least 6.5.0.0
if (!interface_exists('\League\Flysystem\FilesystemOperator')) {
    interface FilesystemOperator extends FilesystemInterface
    {
    }
}
