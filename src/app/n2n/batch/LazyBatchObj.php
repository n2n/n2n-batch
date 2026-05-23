<?php

namespace n2n\batch;

use n2n\util\magic\MagicObjectUnavailableException;
use n2n\core\container\N2nContext;
class LazyBatchObj {
	private ?\ReflectionClass $class = null;
    private ?object $object = null;

    public function __construct(public readonly string $className, public readonly N2nContext $n2nContext) {
    }

	function getClass(): \ReflectionClass {
		if ($this->class !== null) {
			return $this->class;
		}

		try {
			return $this->class = new \ReflectionClass($this->className);
		} catch (\ReflectionException $e) {
			throw new BatchException('Invalid batch class registered: ' . $this->className, 0, $e);
		}

	}

    function getObject(): object {
        if ($this->object !== null) {
			return $this->object;
        }

		try {
			return $this->object = $this->n2nContext->lookup($this->className);
		} catch (MagicObjectUnavailableException $e) {
			throw new BatchException('Invalid batch class registered: ' . $this->className, 0, $e);
		}
    }
}