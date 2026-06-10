<?php

namespace n2n\batch\message;

use n2n\core\container\N2nContext;
use n2n\batch\attribute\BatchAsyncMessage;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\queue\PolledItemRef;
use n2n\batch\BatchException;
use n2n\util\type\TypeUtils;
use n2n\batch\LazyBatchObj;
use n2n\reflection\attribute\MethodAttribute;
use n2n\util\ex\err\FancyError;
use n2n\util\ex\err\ConfigurationError;
use n2n\util\ex\ExUtils;
use n2n\batch\attribute\BatchSyncMessage;

class MessageHandlerInvoker {

	function __construct(private LazyBatchObj $lazyBatchObj) {
	}

	private function createInvoker(MethodAttribute $methodAttribute): MagicMethodInvoker {
		$invoker = new MagicMethodInvoker($this->lazyBatchObj->n2nContext);
		// so mocks and inheritance still works
		$invoker->setMethod(ExUtils::try(fn () => new \ReflectionMethod($this->lazyBatchObj->getObject(),
				$methodAttribute->getMethod()->getName())));
		return $invoker;
	}

	public function invokeSync(MethodAttribute $methodAttribute, object $message): mixed {
		$invoker = $this->createInvoker($methodAttribute);

//		$batchMessageClass = $methodAttribute->getInstance();
//		assert($batchMessageClass instanceof BatchMessageClass);

//		try {
			return $invoker->invoke($this->lazyBatchObj->getObject(), firstArgs: [$message]);
//		} catch (\Error $e) {
//			throw $e;
//		} catch (\Throwable $e) {
//			throw new BatchException(
//					'Batch message handler interrupted: '
//							. TypeUtils::prettyReflMethName($methodAttribute->getMethod()),
//					previous: $e);
//		}
	}

	public function invokeAsync(MethodAttribute $methodAttribute, PolledItemRef $ref): void {
		$invoker = $this->createInvoker($methodAttribute);
		$batchMessageClass = $methodAttribute->getInstance();
		assert($batchMessageClass instanceof BatchAsyncMessage);

		try {
			$this->valReturn(
					$invoker->invoke($this->lazyBatchObj->getObject(), firstArgs: [$ref->data]),
					$methodAttribute);
			$ref->ack();
		} catch (\Error $e) {
			$ref->reject(true);
			throw $e;
		} catch (\Throwable $e) {
			$ref->reject($batchMessageClass->requeuedOnFailure);
			throw new BatchException(
					'Async batch message handler interrupted: '
							. TypeUtils::prettyReflMethName($methodAttribute->getMethod())
							. '; Message was ' . ($batchMessageClass->requeuedOnFailure ? ' requeued' : 'not requeued')
							. '.',
					previous: $e);
		}
	}

	private function valReturn(mixed $returnValue, MethodAttribute $methodAttribute): void {
		if ($returnValue === null) {
			return;
		}

		throw new ConfigurationError(TypeUtils::prettyReflMethName($methodAttribute->getMethod())
				. ' is configured as async message handler and returned a value of type '
				. TypeUtils::getTypeInfo($returnValue)
				. ' but async message handlers must no return a result. Change attribute to '
				. BatchSyncMessage::class . ' if you want it handled synchronously and being able to return a result.');
	}

}