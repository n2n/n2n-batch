<?php

namespace n2n\batch\message;

use n2n\core\container\N2nContext;
use n2n\batch\attribute\BatchMessageClass;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\queue\PolledItemRef;
use n2n\batch\BatchException;
use n2n\util\type\TypeUtils;
use n2n\batch\LazyBatchObj;
use n2n\reflection\attribute\MethodAttribute;

class MessageHandlerInvoker {

	function __construct(private LazyBatchObj $lazyBatchObj) {
	}

	public function invoke(MethodAttribute $methodAttribute, PolledItemRef $ref): void {
		$invoker = new MagicMethodInvoker($this->lazyBatchObj->n2nContext);
		$invoker->setMethod($methodAttribute->getMethod());
		$batchMessageClass = $methodAttribute->getInstance();
		assert($batchMessageClass instanceof BatchMessageClass);

		try {
			$invoker->invoke($this->lazyBatchObj->getObject(), firstArgs: [$ref->data]);
			$ref->ack();
		} catch (\Throwable $e) {
			$ref->reject($batchMessageClass->requeuedOnFailure);
			throw new BatchException(
					'Batch message handler interrupted: '
							. TypeUtils::prettyReflMethName($methodAttribute->getMethod()),
					previous: $e);
		}
	}


}