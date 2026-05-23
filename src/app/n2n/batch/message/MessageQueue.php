<?php

namespace n2n\batch\message;

use n2n\queue\QueueStorePool;
use n2n\queue\PolledItemRef;

class MessageQueue {

	function __construct(private QueueStorePool $queueStorePool) {

	}

	function addAndPoll(string $messageClassName, object $message): PolledItemRef {
		return $this->queueStorePool
				->lookupQueueStore($messageClassName)
				->addAndPoll($message);
	}

	function poll(string $messageClassName): ?PolledItemRef {
		return $this->queueStorePool
				->lookupQueueStore($messageClassName)
				->poll();
	}
}