<?php

namespace n2n\batch\message;

use n2n\queue\QueueStorePool;
use n2n\queue\PolledItemRef;
use n2n\util\type\TypeUtils;

class MessageQueue {

	function __construct(private QueueStorePool $queueStorePool) {

	}

	private function createNamespace(\ReflectionMethod $method, string $messageClassName): string {
		return $method->getDeclaringClass()->getName()
				. '\\' . $method->getName() . '\\' . $messageClassName;
	}

	function addAndPoll(\ReflectionMethod $method, string $messageClassName, object $message): PolledItemRef {
		return $this->queueStorePool
				->lookupQueueStore($this->createNamespace($method, $messageClassName), $messageClassName)
				->addAndPoll($message);
	}

	function poll(\ReflectionMethod $method, string $messageClassName): ?PolledItemRef {
		return $this->queueStorePool
				->lookupQueueStore($this->createNamespace($method, $messageClassName), $messageClassName)
				->poll();
	}

	function clear(): void {
		$this->queueStorePool->clear();
	}
}