<?php

namespace n2n\batch\message;

use n2n\reflection\attribute\MethodAttribute;
use n2n\batch\LazyBatchObj;
use n2n\batch\BatchException;
use n2n\util\type\TypeUtils;
use n2n\batch\attribute\BatchAsyncMessage;
use n2n\batch\attribute\BatchSyncMessage;

class BatchMessageDispatchResult {

	public function __construct(LazyBatchObj $lazyBatchObj, private MethodAttribute $messageClassAttribute,
			readonly bool $async, private object $message, public readonly mixed $return = null) {
	}

	/**
	 * @template T
	 * @param class-string<T> $typeName
	 * @return T
	 */
	public function readReturnObj(string $typeName): mixed {
		if ($this->return instanceof $typeName) {
			return $this->return;
		}

		if (!$this->async) {
			throw new BatchException('Return object of type "' . $typeName . '" expected for message of type '
					. get_class($this->message) . ', but handler '
					. TypeUtils::prettyReflMethName($this->messageClassAttribute->getMethod())
					. ' returned a value of type ' . TypeUtils::getTypeInfo($this->return));
		}

		throw new BatchException('Return object of type "' . $typeName . '" expected for message of type '
				. get_class($this->message) . ', but was handled asynchronously by  '
				. TypeUtils::prettyReflMethName($this->messageClassAttribute->getMethod())
				. ' and async message handler may not return a result object. Change attribute to '
				. BatchSyncMessage::class . ' if you want it handled synchronously.');
	}

	public \ReflectionMethod $method {
		get => $this->messageClassAttribute->getMethod();
	}
}