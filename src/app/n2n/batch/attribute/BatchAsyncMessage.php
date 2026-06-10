<?php

namespace n2n\batch\attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class BatchAsyncMessage {
	function __construct(public string $className, public bool $requeuedOnFailure = true) {
	}
}