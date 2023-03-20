<?php

namespace n2n\batch\ext;

use n2n\core\config\AppConfig;
use n2n\core\cache\AppCache;
use n2n\core\container\impl\AppN2nContext;

class BatchN2nExtension implements \n2n\core\ext\N2nExtension {

	public function __construct(AppConfig $appConfig, AppCache $appCache) {
	}

	function setUp(AppN2nContext $appN2nContext): void {

	}
}