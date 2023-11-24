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
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\ISpeechToTextManager;

class SttService {

	public function __construct(
		private ISpeechToTextManager $manager,
		private IRootFolder $rootFolder,
	) {
	}

	private function getFileObject(string $userId, string $audioContent): File {
		$randomId = bin2hex(random_bytes(6));
		$fileid = $userId . '___' . $randomId;

		$fileObj = new FileService($fileid, $audioContent);

		return $fileObj;
	}

	/**
	 * @param string $path
	 * @param string|null $userId
	 * @return string The transcript
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws PreConditionNotMetException
	 * @throws InvalidArgumentException
	 */
	public function transcribeFile(string $path, ?string $userId): string {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$audioFile = $userFolder->get($path);
		return $this->manager->transcribeFile($audioFile);
	}

	/**
	 * @param string $audioContent
	 * @param string|null $userId
	 * @return string The transcript
	 * @throws NotPermittedException
	 * @throws PreConditionNotMetException
	 * @throws InvalidArgumentException
	 */
	public function transcribeAudio(string $audioContent, ?string $userId): string {
		$audioFile = $this->getFileObject($userId ?? '', $audioContent);
		return $this->manager->transcribeFile($audioFile);
	}
}
