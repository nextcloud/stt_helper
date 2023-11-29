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

namespace OCA\Stt\Listener;

use DateTime;
use OCA\Stt\AppInfo\Application;
use OCA\Stt\Db\Transcript;
use OCA\Stt\Db\TranscriptMapper;
use OCA\Stt\Service\SttService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\SpeechToText\Events\AbstractTranscriptionEvent;
use OCP\SpeechToText\Events\TranscriptionFailedEvent;
use OCP\SpeechToText\Events\TranscriptionSuccessfulEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event>
 */
class SttResultListener implements IEventListener {
	public function __construct(
		private SttService $sttService,
		private TranscriptMapper $transcriptMapper,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof AbstractTranscriptionEvent || $event->getAppId() !== Application::APP_ID) {
			return;
		}

		if ($event instanceof TranscriptionSuccessfulEvent) {
			$transcript = $event->getTranscript();
			$userId = $event->getUserId();

			try {
				$transcriptEntity = new Transcript();
				$transcriptEntity->setUserId($userId ?? '');
				$transcriptEntity->setTranscript($transcript);
				// never seen transcripts should also be deleted in the cleanup job
				$transcriptEntity->setLastAccessed(new DateTime());
				$transcriptEntity = $this->transcriptMapper->insert($transcriptEntity);

				$id = $transcriptEntity->getId();
				$this->sttService->sendNotification($id, $userId, true, $transcript);
			} catch (\Exception $e) {
				$this->logger->error('Failed to save transcript in DB: ' . $e->getMessage());
				$this->sttService->sendNotification(0, $userId, false, 'Failed to save transcript in DB');
			}
		}

		if ($event instanceof TranscriptionFailedEvent) {
			$error = $event->getErrorMessage();
			$userId = $event->getUserId();
			$this->logger->error('Transcript generation failed: ' . $event->getErrorMessage());
			$this->sttService->sendNotification(0, $userId, false, $error);
		}
	}
}
