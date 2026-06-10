<?php

namespace n2n\batch\message;

use n2n\util\ex\IllegalStateException;
use n2n\queue\PolledItemRef;
use n2n\reflection\attribute\MethodAttribute;
use n2n\batch\LazyBatchObj;
use n2n\batch\attribute\BatchAsyncMessage;

class PendingAsyncMessageDispatch {

	function __construct(public readonly LazyBatchObj $lazyBatchObj,
			public readonly MethodAttribute $methodAttribute,
			public readonly object $message) {
	}

	public private(set) PolledItemRef $polledItemRef {
		get {
			IllegalStateException::assertTrue(isset($this->polledItemRef));
			return $this->polledItemRef;
		}
	}

	public string $messageClassName {
		get {
			$messageHandler = $this->methodAttribute->getInstance();
			assert($messageHandler instanceof BatchAsyncMessage);
			return $messageHandler->className;
		}
	}

	public private(set) bool $markedAsStored = false;

	function markAsStored(PolledItemRef $polledItemRef): void {
		IllegalStateException::assertTrue(!$this->markedAsStored, 'Already marked as stored.');

		$this->markedAsStored = true;
		$this->polledItemRef = $polledItemRef;
	}
}