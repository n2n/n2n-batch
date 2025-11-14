<?php

namespace n2n\batch\ext;

use n2n\core\container\N2nContext;

class BatchTriggerResult {
	function __construct(public object $batchJob, public N2nContext $n2nContext) {

	}
}