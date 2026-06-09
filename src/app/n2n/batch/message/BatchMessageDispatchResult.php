<?php

namespace n2n\batch\message;

use n2n\reflection\attribute\MethodAttribute;
use n2n\batch\LazyBatchObj;
use n2n\batch\BatchException;
use n2n\util\type\TypeUtils;
use n2n\batch\attribute\BatchMessageClass;

class BatchMessageDispatchResult {

	public function __construct(LazyBatchObj $lazyBatchObj, private MethodAttribute $messageClassAttribute,
			private object $message, public readonly mixed $return = null) {
	}

	public bool $async {
		get => $this->messageClassAttribute->getInstance()->async;
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
				. ' and async message handler may not return a result object. Turn ' .
				TypeUtils::prettyPropName(BatchMessageClass::class, 'async')
				. ' attribute to false if you want it handled synchronously.');
	}
}