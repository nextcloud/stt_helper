<?php

namespace OCA\Stt\Tests;

use OCA\Stt\AppInfo\Application;
use OCA\Stt\Service\SttService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Notification\IManager as INotifyManager;
use OCP\SpeechToText\ISpeechToTextManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SttServiceTest extends TestCase {

	private const TEST_FILE = 'tests/data/text_sample_file.mp3';

	private SttService $service;
	/** @var ISpeechToTextManager|MockObject */
	private $manager;
	/** @var IRootFolder|MockObject */
	private $rootFolder;
	/** @var INotifyManager|MockObject */
	private $notificationManager;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var IConfig|MockObject */
	private $config;

	public function setUp(): void {
		parent::setUp();

		// setup dummy objects
		$this->manager = $this->createMock(ISpeechToTextManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->notificationManager = $this->createMock(INotifyManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->config = $this->createMock(IConfig::class);

		$this->service = new SttService(
			$this->manager,
			$this->rootFolder,
			$this->notificationManager,
			$this->urlGenerator,
			$this->logger,
			$this->config,
		);
	}

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('stt_helper', $app::APP_ID);
	}

	public function testFileTranscription() {
		$filepath = 'music.mp3';
		$userId = 'dummy';

		$userFolder = $this->createMock(Folder::class);
		$file = $this->createMock(File::class);

		$this->rootFolder->method('getUserFolder')->with($userId)->willReturn($userFolder);
		$userFolder->method('get')->with($filepath)->willReturn($file);
		$this->manager
			->expects($this->once())
			->method('scheduleFileTranscription')
			->with($file)
		;

		$this->service->transcribeFile($filepath, $userId);

		// null userId
		$this->expectException(\InvalidArgumentException::class);
		$this->service->transcribeFile($filepath, null);
	}

	public function testAudioTranscriptionWithoutUserId() {
		// null userId
		$this->expectException(\InvalidArgumentException::class);
		$this->service->transcribeAudio('', null);
	}

	/**
	 * @dataProvider caseBank
	 */
	public function testAudioTranscription(
		string $configValue,
		string $finalFolderName,
		bool $folderExists,
		string $existingFolderName = Application::REC_FOLDER,
	) {
		static $userId = 'dummy';

		$userFolder = $this->createMock(Folder::class);
		$recFolder = $this->createMock(Folder::class);
		$audioFile = $this->createMock(File::class);

		$this->rootFolder->method('getUserFolder')->with($userId)->willReturn($userFolder);

		$userFolder->method('nodeExists')->willReturnCallback(function (
			string $name,
		) use ($existingFolderName, $folderExists) {
			return $name === $existingFolderName && $folderExists;
		});
		$userFolder->method('get')->with($finalFolderName)->willReturn($recFolder);
		$userFolder->method('newFolder')->willReturn($recFolder);

		$recFolder->method('getName')->willReturn($finalFolderName);
		$recFolder->method('newFile')->willReturnCallback(function (
			string $filename,
			mixed $data,
		) use (&$audioFile) {
			$audioFile->method('getName')->willReturn($filename);
			$audioFile->method('fopen')->willReturn($data);
			return $audioFile;
		});

		$this->config
			->method('getAppValue')
			->with(Application::APP_ID, 'stt_folder', '(not set)')
			->willReturn($configValue)
		;
		$this->config
			->method('setAppValue')
			->willReturnCallback(function (string $appId, string $key, string $value) use (&$finalFolderName) {
				if ($appId !== Application::APP_ID || $key !== 'stt_folder') {
					throw new \InvalidArgumentException('Invalid arguments to setAppValue');
				}
				if ($value !== $finalFolderName) {
					throw new \InvalidArgumentException('Invalid value to setAppValue');
				}
			})
		;

		/** @var ?File */
		$receivedFile = null;

		$this->manager
			->expects($this->once())
			->method('scheduleFileTranscription')
			->with($audioFile)
			->willReturnCallback(function (File $file) use (&$receivedFile) {
				$receivedFile = $file;
			})
		;

		$this->service->transcribeAudio(static::TEST_FILE, $userId);

		$this->assertNotEquals($receivedFile, null);
		$this->assertEquals($receivedFile->getName(), $audioFile->getName());

		$receivedFileContents = fread($receivedFile->fopen('rb'), filesize(static::TEST_FILE));
		$testFileContents = file_get_contents(static::TEST_FILE);
		$this->assertEquals($receivedFileContents, $testFileContents);
	}

	public static function caseBank(): array {
		return [
			// case '(not set)', folder 'Application::REC_FOLDER' does not exist
			['(not set)', Application::REC_FOLDER, false],
			// case '(not set)', folder 'Application::REC_FOLDER' does exist
			['(not set)', Application::REC_FOLDER . ' 1', true],
			// case 'Application::REC_FOLDER' folder 'Application::REC_FOLDER' does exist
			[Application::REC_FOLDER, Application::REC_FOLDER, true],
		];
	}
}
