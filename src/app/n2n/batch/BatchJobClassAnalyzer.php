<?php

namespace n2n\batch;

use n2n\reflection\attribute\MethodAttribute;
use n2n\reflection\ReflectionContext;
use n2n\batch\attribute\BatchAsyncMessage;
use n2n\batch\attribute\BatchSyncMessage;

class BatchJobClassAnalyzer {

	function __construct(private \ReflectionClass $class) {

	}

	/**
	 * @return MethodAttribute[]
	 */
	function findBatchAsyncMessageAttributes(): array {
		return ReflectionContext::getAttributeSet($this->class)
				->getMethodAttributesByName(BatchAsyncMessage::class);
	}

	function findBatchAsyncMessageAttribute(string $messageClassName): ?MethodAttribute {
		$methodAttributes = self::findBatchAsyncMessageAttributes();

		foreach ($methodAttributes as $methodAttribute) {
			$batchInput = $methodAttribute->getInstance();
			assert($batchInput instanceof BatchAsyncMessage);

			if ($batchInput->className === $messageClassName) {
				return $methodAttribute;
			};
		}

		return null;
	}

	/**
	 * @return MethodAttribute[]
	 */
	function findBatchSyncMessageAttributes(): array {
		return ReflectionContext::getAttributeSet($this->class)
				->getMethodAttributesByName(BatchSyncMessage::class);
	}

	function findBatchSyncMessageAttribute(string $messageClassName): ?MethodAttribute {
		$methodAttributes = self::findBatchSyncMessageAttributes();

		foreach ($methodAttributes as $methodAttribute) {
			$batchInput = $methodAttribute->getInstance();
			assert($batchInput instanceof BatchSyncMessage);

			if ($batchInput->className === $messageClassName) {
				return $methodAttribute;
			};
		}

		return null;
	}
}