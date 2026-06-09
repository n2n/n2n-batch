<?php

namespace n2n\batch\mock;

use n2n\batch\attribute\BatchMessageClass;
use n2n\context\attribute\ThreadScoped;
use n2n\util\ex\IllegalStateException;

#[ThreadScoped]
class SyncBatchMessageHandlerMock {

	public array $handledMessageMocks = [];

	#[BatchMessageClass(MessageMock::class, async: false)]
	function handleMessageMock(MessageMock $messageMock): void {
		$this->handledMessageMocks[] = $messageMock;
	}

}