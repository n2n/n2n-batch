<?php

namespace n2n\batch;

use n2n\util\type\ArgUtils;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\util\col\ArrayUtils;
use n2n\core\container\N2nContext;

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

	/**
	 * @param string $batchJobClassName
	 * @param \DateTimeImmutable $now
	 * @param N2nContext $n2nContext
	 */
	function triggerBatchJob(string $batchJobClassName, \DateTimeImmutable $now, N2nContext $n2nContext): void {
		$lastTriggeredDateTime = $this->triggerTracker->getLastTriggered($batchJobClassName);

		try {
			$batchJob = $n2nContext->lookup($batchJobClassName);
			$triggerInvestigator = new TriggerInvestigator($batchJob, $now, $lastTriggeredDateTime, $n2nContext);
			$triggerInvestigator->checkAll();
		} catch (MagicObjectUnavailableException $e) {
			throw new BatchException('Invalid BatchJob registered: ' . $batchJobClassName, 0, $e);
		}

		$this->triggerTracker->setLastTriggered($batchJobClassName, $now);
	}
}