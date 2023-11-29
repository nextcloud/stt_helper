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

namespace OCA\Stt\Controller;

use DateTime;
use Exception;
use InvalidArgumentException;
use OCA\Stt\AppInfo\Application;
use OCA\Stt\Db\TranscriptMapper;
use OCA\Stt\Service\SttService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SttController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private SttService $service,
		private LoggerInterface $logger,
		private IL10N $l10n,
		private TranscriptMapper $transcriptMapper,
		private IInitialState $initialState,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param int $id
	 * @return TemplateResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'resultPage')]
	public function getResultPage(int $id): TemplateResponse {
		$response = new TemplateResponse(Application::APP_ID, 'resultPage');
		try {
			$initData = [
				'status' => 'success',
				'transcript' => $this->internalGetTranscript($id),
			];
		} catch (Exception $e) {
			$initData = [
				'status' => 'failure',
				'message' => $e->getMessage(),
			];
			$response->setStatus(intval($e->getCode()));
			$response->throttle(['userId' => $this->userId, 'id' => $id]);
		}
		$this->initialState->provideInitialState('result', $initData);
		return $response;
	}

	/**
	 * @param int $id Transcript ID
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getTranscript(int $id): DataResponse {
		try {
			return new DataResponse($this->internalGetTranscript($id));
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), intval($e->getCode()));
		}
	}

	/**
	 * Internal function to get transcript and throw a common exception
	 *
	 * @param integer $id
	 * @return string
	 */
	private function internalGetTranscript(int $id): string {
		try {
			$transcriptEntity = $this->transcriptMapper->find($id, $this->userId);
			$transcript = $transcriptEntity->getTranscript();

			$transcriptEntity->setLastAccessed(new DateTime());
			$this->transcriptMapper->update($transcriptEntity);

			return trim($transcript);
		} catch (InvalidArgumentException $e) {
			$this->logger->error(
				'Invalid argument in transcript access time update call: ' . $e->getMessage(),
				['app' => Application::APP_ID],
			);
			throw new Exception(
				$this->l10n->t('Error in transcript access time update call'),
				Http::STATUS_INTERNAL_SERVER_ERROR,
			);
		} catch (MultipleObjectsReturnedException $e) {
			$this->logger->error('Multiple transcripts found: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			throw new Exception($this->l10n->t('Multiple transcripts found'), Http::STATUS_BAD_REQUEST);
		} catch (DoesNotExistException $e) {
			throw new Exception($this->l10n->t('Transcript not found'), Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			$this->logger->error('Error: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			throw new Exception(
				$this->l10n->t('Some internal error occurred, check logs to find out more'),
				Http::STATUS_INTERNAL_SERVER_ERROR,
			);
		}
	}

	/**
	 * @param string $audioBase64
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function transcribeAudio(string $audioBase64, bool $schedule): DataResponse {
		if ($audioBase64 === '') {
			return new DataResponse('Invalid audio data received', Http::STATUS_BAD_REQUEST);
		}

		$audioContent = base64_decode(str_replace('data:audio/mp3;base64,', '', $audioBase64), true);
		if ($audioContent === false) {
			return new DataResponse('Invalid audio data received', Http::STATUS_BAD_REQUEST);
		}

		try {
			$transcription = $this->service->transcribeAudio($audioContent, $schedule, $this->userId);
			return new DataResponse($transcription);
		} catch (RuntimeException $e) {
			$this->logger->error(
				'Runtime exception: ' . $e->getMessage(),
				['app' => Application::APP_ID]
			);
			return new DataResponse(
				$this->l10n->t('Some internal error occurred. Check logs to find out more.'),
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		} catch (PreConditionNotMetException $e) {
			$this->logger->error('No Speech-to-Text provider found: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new DataResponse(
				$this->l10n->t('No Speech-to-Text provider found, install one from the app store to use this feature.'),
				Http::STATUS_BAD_REQUEST
			);
		} catch (InvalidArgumentException $e) {
			$this->logger->error('InvalidArgumentException: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new DataResponse(
				$this->l10n->t('Some internal error occurred. Check logs to find out more.'),
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}

	/**
	 * @param string $path Nextcloud file path
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function transcribeFile(string $path, bool $schedule): DataResponse {
		if ($path === '') {
			return new DataResponse('Empty file path received', Http::STATUS_BAD_REQUEST);
		}

		try {
			$transcription = $this->service->transcribeFile($path, $schedule, $this->userId);
			return new DataResponse($transcription);
		} catch (NotFoundException $e) {
			$this->logger->error('Audio file not found: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new DataResponse(
				$this->l10n->t('Audio file not found.'),
				Http::STATUS_NOT_FOUND
			);
		} catch (RuntimeException $e) {
			$this->logger->error(
				'Runtime exception: ' . $e->getMessage(),
				['app' => Application::APP_ID]
			);
			return new DataResponse(
				$this->l10n->t('Some internal error occurred. Check logs to find out more.'),
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		} catch (NotPermittedException $e) {
			$this->logger->error(
				'No permission to create recording file/directory: ' . $e->getMessage(),
				['app' => Application::APP_ID]
			);
			return new DataResponse(
				$this->l10n->t('No permission to create recording file/directory, check log files and your installation.'),
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		} catch (PreConditionNotMetException $e) {
			$this->logger->error('No Speech-to-Text provider found: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new DataResponse(
				$this->l10n->t('No Speech-to-Text provider found, install one from the app store to use this feature.'),
				Http::STATUS_BAD_REQUEST
			);
		} catch (InvalidArgumentException $e) {
			$this->logger->error('InvalidArgumentException: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new DataResponse(
				$this->l10n->t('Some internal error occurred. Check logs to find out more.'),
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}
}
