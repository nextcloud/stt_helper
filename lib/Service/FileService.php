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

use OC\Files\Node\Node;
use OCP\Files\File;

class FileService extends Node implements File {

	private string $fileid;
	private string $content;
	private string $hash;

	public function __construct(string $fileid, string $audioContent) {
		$this->fileid = $fileid;
		$this->content = $audioContent;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @param string $data
	 */
	public function putContent($data) {
		$this->content = $data;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->fileid . '.mp3';
	}

	/**
	 * @return string
	 */
	public function getMimeType() {
		return 'audio/mpeg';
	}

	/**
	 * @param string $mode
	 * @return resource|false
	 */
	public function fopen($mode) {
		return false;
	}

	/**
	 * @param string $type
	 * @param bool $raw
	 * @return string
	 */
	public function hash($type, $raw = false) {
		$this->hash = hash($type, $this->content, $raw);
		return $this->hash;
	}

	public function getChecksum() {
	}

	public function getExtension(): string {
		return 'mp3';
	}
}
