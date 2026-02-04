<?php

namespace n2n\batch;

use n2n\util\type\ArgUtils;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\util\col\ArrayUtils;
use n2n\core\container\N2nContext;
use n2n\batch\ext\BatchTriggerResult;

class BatchWorker {

	function __construct(private array $batchJobClassNames, private TriggerTracker $triggerTracker) {
		ArgUtils::valArray($this->batchJobClassNames, 'string');
	}

	private function ensureBatchJobClassNameExists(string $batchJobClassName): void {
		if (ArrayUtils::contains($this->batchJobClassNames, $batchJobClassName)) {
			return;
		}

		throw new \InvalidArgumentException('Unknown batch job: ' . $batchJobClassName);
	}

	function updateLastTriggerDateTime(string $batchJobClassName, \DateTimeImmutable $dateTime): void {
		$this->ensureBatchJobClassNameExists($batchJobClassName);

		$this->triggerTracker->setLastTriggered($batchJobClassName, $dateTime);
	}

	/**
	 * @return string[]
	 */
	function getBatchJobClassNames(): array {
		return $this->batchJobClassNames;
	}

	function triggerBatchJob(string $batchJobClassName, \DateTimeImmutable $now, N2nContext $n2nContext): ?BatchTriggerResult {
		$this->ensureBatchJobClassNameExists($batchJobClassName);

		$lastTriggeredDateTime = $this->triggerTracker->getLastTriggered($batchJobClassName);

		try {
			$batchJob = $n2nContext->lookup($batchJobClassName);
		} catch (MagicObjectUnavailableException $e) {
			throw new BatchException('Invalid BatchJob registered: ' . $batchJobClassName, 0, $e);
		}

		$triggerInvestigator = new TriggerInvestigator($batchJob, $now, $lastTriggeredDateTime, $n2nContext);
		if (!$triggerInvestigator->checkAll()) {
			return null;
		}

		$this->triggerTracker->setLastTriggered($batchJobClassName, $now);
		return new BatchTriggerResult($batchJob, $n2nContext);
	}
}