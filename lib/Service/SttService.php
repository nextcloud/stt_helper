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

namespace OCA\Stt\Service;

use InvalidArgumentException;
use OCA\Stt\AppInfo\Application;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IURLGenerator;
use OCP\Notification\IManager as INotifyManager;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\ISpeechToTextManager;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SttService {

	public function __construct(
		private ISpeechToTextManager $manager,
		private IRootFolder $rootFolder,
		private INotifyManager $notificationManager,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Sends a notification to the user for successful or failed transcription
	 *
	 * @param int $id
	 * @param string $userId
	 * @param boolean $success
	 * @param string $message The transcript or error message
	 * @return void
	 */
	public function sendNotification(int $id, string $userId, bool $success, string $message) {
		$notification = $this->notificationManager->createNotification();

		$subject = $success ? 'success' : 'failure';
		$params = [
			'id' => $id,
			'message' => $message,
		];
		$url = $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_ID . '.stt.getResultPage', ['id' => $id]
		);

		$notification->setApp(Application::APP_ID)
			->setUser($userId)
			->setDateTime(new \DateTime())
			->setObject('transcript', strval($id))
			->setSubject($subject, $params)
			->setLink($url)
		;

		$this->notificationManager->notify($notification);
	}

	private function getFileObject(string $userId, string $audioContent): File {
		$randomId = bin2hex(random_bytes(6));
		$fileid = $userId . '___' . $randomId;

		$fileObj = new FileService($fileid, $audioContent);

		return $fileObj;
	}

	/**
	 * @param string $path
	 * @param bool $schedule
	 * @param string|null $userId
	 * @return string The transcript
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws PreConditionNotMetException
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function transcribeFile(string $path, bool $schedule, ?string $userId): string {
		// this also prevents NoUserException
		if ($userId === null) {
			throw new InvalidArgumentException('userId must not be null');
		}

		$userFolder = $this->rootFolder->getUserFolder($userId);
		$audioFile = $userFolder->get($path);

		if ($schedule) {
			$this->manager->scheduleFileTranscription($audioFile, $userId, Application::APP_ID);
			return 'ok';
		}

		return $this->manager->transcribeFile($audioFile);
	}

	/**
	 * @param string $audioContent
	 * @param bool $schedule
	 * @param string|null $userId
	 * @return string The transcript
	 * @throws NotPermittedException
	 * @throws PreConditionNotMetException
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function transcribeAudio(string $audioContent, bool $schedule, ?string $userId): string {
		$audioFile = $this->getFileObject($userId ?? '', $audioContent);

		if ($schedule) {
			$this->manager->scheduleFileTranscription($audioFile, $userId, Application::APP_ID);
			return 'ok';
		}

		return $this->manager->transcribeFile($audioFile);
	}
}
