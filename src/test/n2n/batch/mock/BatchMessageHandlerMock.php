<?php

namespace n2n\batch\mock;

use n2n\batch\attribute\BatchMessageClass;
use n2n\context\attribute\ThreadScoped;
use n2n\util\ex\IllegalStateException;

#[ThreadScoped]
class BatchMessageHandlerMock {

	public array $handledMessageMocks = [];

	#[BatchMessageClass(MessageMock::class)]
	function handleMessageMock(MessageMock $messageMock): void {
		$this->handledMessageMocks[] = $messageMock;
	}

	#[BatchMessageClass(FailingRequeueMessageMock::class, requeuedOnFailure: true)]
	function handleFailingRequeueMessageMock(FailingRequeueMessageMock $messageMock): void {
		throw new IllegalStateException();
	}

	#[BatchMessageClass(FailingNoRequeueMessageMock::class, requeuedOnFailure: false)]
	function handleFailingNoRequeueMessageMock(FailingNoRequeueMessageMock $messageMock): void {
		throw new IllegalStateException();
	}
}