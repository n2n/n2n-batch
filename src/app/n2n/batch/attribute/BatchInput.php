<?php

namespace n2n\batch\attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class BatchInput {
	function __construct(public string $className) {
	}
}