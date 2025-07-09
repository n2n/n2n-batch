<?php

namespace n2n\batch\ext;

use n2n\core\config\AppConfig;
use n2n\core\cache\AppCache;
use n2n\core\container\impl\AppN2nContext;
use n2n\core\ext\ConfigN2nExtension;
use n2n\core\N2nApplication;

class BatchN2nExtension implements ConfigN2nExtension {

	public function __construct(N2nApplication $n2nApplication) {
	}

	function applyToN2nContext(AppN2nContext $appN2nContext): void {

	}
}