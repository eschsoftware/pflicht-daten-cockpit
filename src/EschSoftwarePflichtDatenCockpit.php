<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit;

use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerDataCleaner;
use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerFieldInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
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

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $environment = $container->getParameter('kernel.environment');
        if (!\is_string($environment) || !\in_array($environment, ['dev', 'test'], true)) {
            return;
        }

        $configDir = __DIR__ . '/Resources/config';
        if (is_file($configDir . '/services_dev.xml')) {
            $loader = new XmlFileLoader($container, new FileLocator($configDir));
            $loader->load('services_dev.xml');
        }

        $commandServices = __DIR__ . '/Command/services.xml';
        if (is_file($commandServices)) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Command'));
            $loader->load('services.xml');
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        $this->removePublicAssets();

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->fields()->removeFields($uninstallContext->getContext());
        $this->cleaner()->removeStoredValues();
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

    private function cleaner(): GpsrManufacturerDataCleaner
    {
        $connection = $this->container->get('Doctrine\DBAL\Connection');

        if (!$connection instanceof \Doctrine\DBAL\Connection) {
            throw new \RuntimeException('Doctrine\DBAL\Connection is not available.');
        }

        return new GpsrManufacturerDataCleaner($connection);
    }

    private function removePublicAssets(): void
    {
        $projectDir = $this->container->getParameter('kernel.project_dir');
        if (!\is_string($projectDir) || $projectDir === '') {
            return;
        }

        $directory = $projectDir . '/public/bundles/' . mb_strtolower($this->getName());
        if (!is_dir($directory)) {
            return;
        }

        (new Filesystem())->remove($directory);
    }
}
