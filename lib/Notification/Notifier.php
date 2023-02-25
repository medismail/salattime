<?php

declare(strict_types=1);

namespace OCA\SalatTime\Notification;

use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCA\SalatTime\AppInfo\Application;

class Notifier implements \OCP\Notification\INotifier {
	protected $factory;
	protected $url;

	public function __construct(\OCP\L10N\IFactory $factory,
								\OCP\IURLGenerator $urlGenerator) {
		$this->factory = $factory;
		$this->url = $urlGenerator;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 * @return string
	 */
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * Human readable name describing the notifier
	 * @return string
	 */
	public function getName(): string {
		return $this->factory->get(Application::APP_ID)->t('Salat Time');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			// Not my app => throw
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get(Application::APP_ID, $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'Adhen for salat':
				$dateNow = new \DateTime();
				if ($notification->getDateTime() > $dateNow) {
					//Not time yet
					throw new \InvalidArgumentException();
				} else {
                        		$notification->setParsedSubject($l->t('Adhen for salat ') . $l->t($notification->getObjectId()) . '.')
                        	        	->setParsedMessage($l->t('Please do not delay your salat.'));
					$notification->setIcon($this->url->imagePath(Application::APP_ID, 'app-dark.svg'));
					return $notification;
				}
			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}

}
