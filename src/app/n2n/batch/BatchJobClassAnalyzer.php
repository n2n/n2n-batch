<?php

namespace n2n\batch;

use n2n\reflection\attribute\MethodAttribute;
use n2n\reflection\ReflectionContext;
use n2n\batch\attribute\BatchMessageClass;

class BatchJobClassAnalyzer {

	function __construct(private \ReflectionClass $class) {

	}

	/**
	 * @return MethodAttribute[]
	 */
	function findBatchInputAttributes(): array {
		return ReflectionContext::getAttributeSet($this->class)
				->getMethodAttributesByName(BatchMessageClass::class);
	}

	function findBatchInputAttribute(string $inputClassName): ?MethodAttribute {
		$methodAttributes = self::findBatchInputAttributes();

		foreach ($methodAttributes as $methodAttribute) {
			$batchInput = $methodAttribute->getInstance();
			assert($batchInput instanceof BatchMessageClass);

			if ($batchInput->className === $inputClassName) {
				return $methodAttribute;
			};
		}

		return null;
	}
}