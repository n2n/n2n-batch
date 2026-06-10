<?php

namespace n2n\batch\mock;

use n2n\batch\attribute\BatchAsyncMessage;
use n2n\context\attribute\ThreadScoped;
use n2n\util\ex\IllegalStateException;

#[ThreadScoped]
class AsyncBatchMessageHandlerMock {

	public array $handledMessageMocks = [];

	#[BatchAsyncMessage(MessageMock::class)]
	function handleMessageMock(MessageMock $messageMock): void {
		$this->handledMessageMocks[] = $messageMock;
	}

	#[BatchAsyncMessage(FailingRequeueMessageMock::class, requeuedOnFailure: true)]
	function handleFailingRequeueMessageMock(FailingRequeueMessageMock $messageMock): void {
		throw new IllegalStateException();
	}

	#[BatchAsyncMessage(FailingNoRequeueMessageMock::class, requeuedOnFailure: false)]
	function handleFailingNoRequeueMessageMock(FailingNoRequeueMessageMock $messageMock): void {
		throw new IllegalStateException();
	}
}