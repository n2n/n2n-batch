<?php

namespace n2n\batch\ext;

use n2n\core\ext\N2nBatch;
use n2n\core\ext\BatchTriggerConfig;
use n2n\core\N2nApplication;
use n2n\core\container\N2nContext;
use n2n\batch\BatchJobRegistry;
use n2n\core\container\impl\AddOnContext;
use n2n\util\magic\impl\SimpleMagicContext;

class BatchAddOnContext implements N2nBatch, AddOnContext {
	private ?SimpleMagicContext $simpleMagicContext;
	private BatchJobRegistry $batchJobRegistry;

	function __construct(private N2nApplication $n2nApplication, private N2nContext $n2nContext) {
		$this->batchJobRegistry = new BatchJobRegistry($this->n2nContext,
				$this->n2nApplication->getAppConfig()->general()->getBatchJobClassNames());

		$this->simpleMagicContext = new SimpleMagicContext([BatchJobRegistry::class => $this->batchJobRegistry]);
	}

	function trigger(?BatchTriggerConfig $config = null): array {
		$registry = $this->n2nContext->lookup(BatchJobRegistry::class);
		$batchWorker = $registry->createBatchWorker();
		$batchJobClassNames = $config?->filterBatchJobNames ?? $batchWorker->getBatchJobClassNames();

		if ($config?->overwriteLastTriggerDateTime !== null) {
			foreach ($batchJobClassNames as $batchJobClassName) {
				$batchWorker->updateLastTriggerDateTime($batchJobClassName, $config->overwriteLastTriggerDateTime);
			}
		}

		$results = [];
		foreach ($batchJobClassNames as $batchJobClassName) {
			$forkedN2nContext = $config->n2nContext ?? $this->n2nApplication->forkN2nContext($this->n2nContext, true);
			$results[] = $batchWorker->triggerBatchJob($batchJobClassName, $config?->dateTime ?? new \DateTimeImmutable(),
						$forkedN2nContext);
		}
		return array_filter($results);
	}

	function finalize(): void {

	}

	function hasMagicObject(string $id): bool {
		return $this->simpleMagicContext->has($id);
	}

	function lookupMagicObject(string $id, bool $required = true, ?string $contextNamespace = null): mixed {
		return $this->simpleMagicContext->lookup($id, false, $contextNamespace);
	}

}