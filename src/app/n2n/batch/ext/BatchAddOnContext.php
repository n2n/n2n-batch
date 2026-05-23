<?php

namespace n2n\batch\ext;

use n2n\core\ext\N2nBatch;
use n2n\core\ext\BatchTriggerConfig;
use n2n\core\N2nApplication;
use n2n\core\container\N2nContext;
use n2n\batch\BatchClassRegistry;
use n2n\core\container\impl\AddOnContext;
use n2n\util\magic\impl\SimpleMagicContext;
use n2n\batch\message\MessageQueue;
use n2n\queue\impl\QueueStorePools;
use n2n\core\VarStore;
use n2n\core\ext\MessageDispatchConfig;

class BatchAddOnContext implements N2nBatch, AddOnContext {
	private ?SimpleMagicContext $simpleMagicContext;
	private BatchClassRegistry $batchJobRegistry;

	function __construct(private N2nApplication $n2nApplication, private N2nContext $n2nContext) {
		$this->batchJobRegistry = new BatchClassRegistry($this->n2nContext,
				$this->n2nApplication->getAppConfig()->general()->getBatchJobClassNames());

		$this->simpleMagicContext = new SimpleMagicContext([BatchClassRegistry::class => $this->batchJobRegistry]);
	}

	function trigger(?BatchTriggerConfig $config = null): array {
		$registry = $this->n2nContext->lookup(BatchClassRegistry::class);
		$batchWorker = $registry->createBatchWorker();
		$batchClassNames = $config?->filterBatchJobNames ?? $batchWorker->getBatchJobClassNames();

		if ($config?->overwriteLastTriggerDateTime !== null) {
			foreach ($batchClassNames as $batchJobClassName) {
				$batchWorker->updateLastTriggerDateTime($batchJobClassName, $config->overwriteLastTriggerDateTime);
			}
		}

		$results = [];
		foreach ($batchClassNames as $batchJobClassName) {
			$forkedN2nContext = $config->n2nContext ?? $this->n2nApplication->forkN2nContext($this->n2nContext, true);
			$results[] = $batchWorker->triggerBatchObj($batchJobClassName,
					$config?->dateTime ?? new \DateTimeImmutable(), $forkedN2nContext);
		}
		return array_filter($results);
	}

	function dispatch(object $message, ?MessageDispatchConfig $config = null): void {
		$registry = $this->n2nContext->lookup(BatchClassRegistry::class);
		$messageDispatcher = $registry->createMessageDispatcher();

		$messageDispatcher->bindToTransactionManager($this->n2nContext->getTransactionManager());

		$messageDispatcher->dispatchMessage($message, $this->n2nContext);
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