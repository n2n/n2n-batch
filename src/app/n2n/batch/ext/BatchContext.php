<?php

namespace n2n\batch\ext;

use n2n\core\ext\N2nBatch;
use n2n\core\ext\BatchTriggerConfig;
use n2n\core\N2nApplication;
use n2n\core\container\N2nContext;
use n2n\batch\BatchJobRegistry;

class BatchContext implements N2nBatch {

	function __construct(private N2nApplication $n2nApplication, private N2nContext $n2nContext) {

	}

	function trigger(?BatchTriggerConfig $config = null): void {
		$registry = $this->n2nContext->lookup(BatchJobRegistry::class);
		$batchWorker = $registry->createBatchWorker();
		$batchJobClassNames = $config?->filterBatchJobNames ?? $batchWorker->getBatchJobClassNames();

		if ($config?->overwriteLastTriggerDateTime !== null) {
			foreach ($batchJobClassNames as $batchJobClassName) {
				$batchWorker->updateLastTriggerDateTime($batchJobClassName, $config->overwriteLastTriggerDateTime);
			}
		}

		foreach ($batchJobClassNames as $batchJobClassName) {
			$forkedN2nContext = $this->n2nApplication->forkN2nContext($this->n2nContext, true);
			try {
				$batchWorker->triggerBatchJob($batchJobClassName, $config?->dateTime ?? new \DateTimeImmutable(),
						$forkedN2nContext);
			} finally {
				$forkedN2nContext->finalize();
			}
		}
	}
}