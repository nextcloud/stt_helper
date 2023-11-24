<?php

namespace OCA\Stt\Tests;

use OCA\Stt\AppInfo\Application;
use OCA\Stt\Service\SttService;
use OCP\Files\IRootFolder;
use OCP\SpeechToText\ISpeechToTextManager;
use Test\TestCase;

class SttServiceTest extends TestCase {

	private SttService $service;
	private ISpeechToTextManager $manager;
	private IRootFolder $rootFolder;

	public function setUp(): void {
		parent::setUp();

		// setup dummy objects
		$this->manager = $this->createMock(ISpeechToTextManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->service = new SttService(
			$this->manager,
			$this->rootFolder,
		);
	}

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('stt_helper', $app::APP_ID);
	}

	public function testFileObject() {
		// we get the transcribeFile function to return the audioFile's content
		//   so we can check if the file object is correct
		//   and since getFileObject is private
		$this->manager->method('transcribeFile')->willReturnCallback(function ($audioFile) {
			return $audioFile->getContent();
		});

		$userId = 'dummy';
		$audioContent = 'badumtss';

		$receivedContent = $this->service->transcribeAudio($audioContent, $userId);

		$this->assertEquals($audioContent, $receivedContent);
	}
}
