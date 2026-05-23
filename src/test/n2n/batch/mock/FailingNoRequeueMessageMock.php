<?php

namespace n2n\batch\mock;

class FailingNoRequeueMessageMock {

	function __construct(public string $prop) {}

}