<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit;

use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerFieldInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class EschSoftwarePflichtDatenCockpit extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->fields()->ensureFields($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->fields()->ensureFields($updateContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        $this->fields()->ensureFields($activateContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->fields()->removeFields($uninstallContext->getContext());
    }

    public function configureRoutes(RoutingConfigurator $routes, string $environment): void
    {
        if (!$this->isActive()) {
            return;
        }

        $routes->import($this->getPath() . '/Resources/config/routes.xml');
    }

    private function fields(): GpsrManufacturerFieldInstaller
    {
        $repository = $this->container->get('custom_field_set.repository');

        if (!$repository instanceof EntityRepository) {
            throw new \RuntimeException('custom_field_set.repository is not available.');
        }

        return new GpsrManufacturerFieldInstaller($repository);
    }
}
