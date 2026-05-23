<?php

namespace n2n\batch\attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class MessageHandler {
	function __construct(public string $className) {
	}
}