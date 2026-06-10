<?php

namespace n2n\batch\attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class BatchSyncMessage {
	function __construct(public string $className) {
	}
}