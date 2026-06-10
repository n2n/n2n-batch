<?php

namespace n2n\batch\mock;

use n2n\context\attribute\ThreadScoped;
use n2n\batch\attribute\BatchSyncMessage;

#[ThreadScoped]
class SyncBatchMessageHandlerMock {

	public array $handledMessageMocks = [];

	#[BatchSyncMessage(MessageMock::class)]
	function handleMessageMock(MessageMock $messageMock): void {
		$this->handledMessageMocks[] = $messageMock;
	}

	#[BatchSyncMessage(SyncMessageMock::class)]
	function handleSyncMessageMock(SyncMessageMock $messageMock): \DateTime {
		$this->handledMessageMocks[] = $messageMock;
		return new \DateTime('1985-09-07');
	}

}