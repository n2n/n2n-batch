<?php

namespace n2n\batch;

use n2n\reflection\attribute\MethodAttribute;
use n2n\reflection\ReflectionContext;
use n2n\batch\attribute\MessageHandler;

class BatchJobClassAnalyzer {

	function __construct(private \ReflectionClass $class) {

	}

	/**
	 * @return MethodAttribute[]
	 */
	function findBatchInputAttributes(): array {
		return ReflectionContext::getAttributeSet($this->class)
				->getMethodAttributesByName(MessageHandler::class);
	}

	function findBatchInputAttribute(string $inputClassName): ?MethodAttribute {
		$methodAttributes = self::findBatchInputAttributes();

		foreach ($methodAttributes as $methodAttribute) {
			$batchInput = $methodAttribute->getInstance();
			assert($batchInput instanceof MessageHandler);

			if ($batchInput->className === $inputClassName) {
				return $methodAttribute;
			};
		}

		return null;
	}
}