<?php

declare(strict_types=1);

namespace OCA\SalatTime\AppInfo;

use OCP\AppFramework\App;
use OCA\SalatTime\Dashboard\SalatTimeWidget;
use OCA\SalatTime\Notification\Notifier;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'salattime';

	public function __construct(array $params = []) {
		parent::__construct(self::APP_ID, $params);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */

		$container->registerService('Config', function($c) {
			return $c->query('ServerContainer')->getConfig();
		});
	}

	public function register(IRegistrationContext $context): void {
		$context->registerDashboardWidget(SalatTimeWidget::class);
		$context->registerNotifierService(Notifier::class);
	}

	public function boot(IBootContext $context): void {
	}
}
