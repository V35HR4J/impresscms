<?php

namespace ImpressCMS\Core\Extensions\SetupSteps\Module\Uninstall;

use icms_module_Object;
use ImpressCMS\Core\Extensions\SetupSteps\OutputDecorator;
use ImpressCMS\Core\Extensions\SetupSteps\SetupStepInterface;
use ImpressCMS\Core\Models\Module;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Flysystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Deletes all module assets
 *
 * @package ImpressCMS\Core\SetupSteps\Module\Uninstall
 */
class CopyAssetsSetupStep implements SetupStepInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * @inheritDoc
	 */
	public function execute(Module $module, OutputDecorator $output, ...$params): bool
	{
		/**
		 * @var Filesystem $fs
		 */
		$fs = $this->container->get('filesystem.public');

		/**
		 * @var TranslatorInterface $trans
		 */
		$trans = $this->container->get('translator');

		$output->info(
			$trans->trans('ADDONS_COPY_ASSETS_DELETING', [], 'addons')
		);
		$fs->deleteDirectory('modules/' . $module->dirname);

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getPriority(): int
	{
		return 100;
	}
}