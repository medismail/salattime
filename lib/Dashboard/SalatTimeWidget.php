<?php
/**
 * @copyright Copyright (c) 2022 Mohamed-Ismail MEJRI <imejri@hotmail.com>
 *
 * @author Mohamed-Ismail MEJRI
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\SalatTime\Dashboard;

use OCP\Dashboard\IWidget;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

use OCA\SalatTime\AppInfo\Application;

class SalatTimeWidget implements IWidget {
	/** @var IL10N */
	private $l10n;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IURLGenerator
	 */
	private $url;

	public function __construct(IL10N $l10n,
								IURLGenerator $url,
								IConfig $config) {
		$this->l10n = $l10n;
		$this->config = $config;
		$this->url = $url;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		$widgetTitle = $this->config->getAppValue(Application::APP_ID, 'widgetTitle', $this->l10n->t('Salat Time'));
		return $widgetTitle ?: $this->l10n->t('Salat Time');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconClass(): string {
		return 'icon-salattime';
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		Util::addScript(Application::APP_ID, Application::APP_ID . '-dashboard');
		Util::addStyle(Application::APP_ID, 'dashboard');
	}
}
