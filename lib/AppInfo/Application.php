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

namespace OCA\Stt\AppInfo;

use OCA\Stt\Listener\BeforeTemplateRenderedListener;
use OCA\Stt\Listener\SttReferenceListener;
use OCA\Stt\Listener\SttResultListener;
use OCA\Stt\Notification\Notifier;
use OCA\Stt\Reference\SttReferenceProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\SpeechToText\Events\TranscriptionFailedEvent;
use OCP\SpeechToText\Events\TranscriptionSuccessfulEvent;

class Application extends App implements IBootstrap {

	public const APP_ID = 'stt_helper';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(RenderReferenceEvent::class, SttReferenceListener::class);
		$context->registerEventListener(TranscriptionSuccessfulEvent::class, SttResultListener::class);
		$context->registerEventListener(TranscriptionFailedEvent::class, SttResultListener::class);
		$context->registerNotifierService(Notifier::class);
		$context->registerReferenceProvider(SttReferenceProvider::class);
	}

	public function boot(IBootContext $context): void {
	}
}
