<?php

/**
 *
 * Salat Time APP (Nextcloud)
 *
 * @author Mohamed-Ismail MEJRI <imejri@hotmail.com>
 *
 * @copyright Copyright (c) 2024 Mohamed-Ismail MEJRI
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OCA\SalatTime\Notification;

use OCP\Notification\INotification;
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
			case 'Adhan for salat':
				$dateNow = new \DateTime();
				if ($notification->getDateTime() > $dateNow) {
					//Not time yet
					throw new \InvalidArgumentException();
				} else {
					$params = $notification->getSubjectParameters();
                    $tSalat = $l->t($notification->getObjectId());
					$notification->setParsedSubject($l->t('The Adhan for salat %s.', [$tSalat]))
                            ->setParsedMessage($l->t('The Adhan for salat %s is at %s, the prayer time ends at %s.%sPerforming prayers is a duty on the believers at the appointed times.', [$tSalat, $params[0], $params[1], chr(0x0D).chr(0x0A)]));
					$notification->setIcon($this->url->imagePath(Application::APP_ID, 'app-dark.svg'));
					return $notification;
				}
				// no break
			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}
}
