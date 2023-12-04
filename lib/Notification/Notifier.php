<?php
/**
 * @copyright Copyright (c) 2023 Anupam Kumar <kyteinsky@gmail.com>
 *
 * @author Anupam Kumar <kyteinsky@gmail.com>
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
 */

namespace OCA\Stt\Notification;

use OCA\Stt\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IAction;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	public function __construct(
		private IFactory $factory,
		private IURLGenerator $urlGenerator,
		private ?string $userId,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->factory->get(Application::APP_ID)->t('Speech To Text App');
	}

	/**
	 * @inheritDoc
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			throw new \InvalidArgumentException();
		}

		$l = $this->factory->get(Application::APP_ID, $languageCode);

		$params = $notification->getSubjectParameters();

		$subject = '';
		$content = '';
		$actionLabel = '';
		$message = $this->cutByWords($params['message']);

		switch ($notification->getSubject()) {
			case 'success':
				$subject = $l->t('Transcription has finished!');
				$content = $l->t('Transcript: %1$s', [$message]);
				$actionLabel = $l->t('View transcript');
				$iconUrl = $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg');
				break;

			case 'failure':
				$subject = $l->t('Transcription has failed!');
				$content = $l->t('Error: %1$s', [$message]);
				$actionLabel = $l->t('View error message');
				$iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/error.svg'));
				break;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}

		$notification
			->setParsedSubject($subject)
			->setParsedMessage($content)
			->setLink($notification->getLink())
			->setIcon($iconUrl)
		;

		$action = $notification->createAction();
		$action
			->setParsedLabel($actionLabel)
			->setLink($notification->getLink(), IAction::TYPE_WEB)
		;

		$notification->addParsedAction($action);

		return $notification;
	}

	private function cutByWords(string $text, int $maxWords = 20): string {
		$words = explode(' ', $text);
		$words = array_slice($words, 0, $maxWords);
		$output = implode(' ', $words);
		if (count($words) < count(explode(' ', $text))) {
			$output .= '...';
		}
		return $output;
	}
}
