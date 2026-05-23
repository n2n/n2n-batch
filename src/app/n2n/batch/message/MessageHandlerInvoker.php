<?php

namespace n2n\batch\message;

use n2n\core\container\N2nContext;
use n2n\batch\attribute\MessageHandler;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\queue\PolledItemRef;

class MessageHandlerInvoker {
	private MessageHandler $messageHandler;

	function __construct(private N2nContext $n2nContext) {
	}

	public function invoke(object $batchObj, \ReflectionMethod $method, PolledItemRef $ref): void {
		$invoker = new MagicMethodInvoker($this->n2nContext);
		$invoker->setMethod($method);

		try {
			$invoker->invoke($batchObj, firstArgs: [$ref->data]);
			$ref->ack();
		} catch (\Throwable $e) {
			$ref->reject(true);
		}
	}


}