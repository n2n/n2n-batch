<?php

namespace n2n\batch\attribute;

use Attribute;

#[\Attribute(Attribute::TARGET_METHOD)]
class BatchInterval {
	function __construct(public \DateInterval $interval) {
	}
}
