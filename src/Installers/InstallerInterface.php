<?php

declare(strict_types=1);

namespace HeidelPayment\Installers;

use Shopware\Core\Framework\Plugin\Context\InstallContext;

interface InstallerInterface
{
    public function install(InstallContext $context): void;

    public function update(InstallContext $context): void;

    public function uninstall(InstallContext $context): void;

    public function activate(InstallContext $context): void;

    public function deactivate(InstallContext $context): void;
}
