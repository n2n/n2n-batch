<?php

namespace n2n\batch;

use n2n\util\type\ArgUtils;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\util\col\ArrayUtils;
use n2n\core\container\N2nContext;
use n2n\batch\ext\BatchTriggerResult;
use n2n\batch\message\MessageQueue;
use n2n\batch\interval\TriggerTracker;

class BatchWorker {

	function __construct(private array $batchJobClassNames, private TriggerTracker $triggerTracker,
			private MessageQueue $messageQueue) {
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

	function triggerBatchObj(string $batchClassName, \DateTimeImmutable $now, N2nContext $n2nContext): ?BatchTriggerResult {
		$this->ensureBatchJobClassNameExists($batchClassName);

		$lastTriggeredDateTime = $this->triggerTracker->getLastTriggered($batchClassName);

		$registeredBatchObj = new LazyBatchObj($batchClassName, $n2nContext);

		$triggerInvestigator = new TriggerInvestigator($registeredBatchObj, $now,
				$lastTriggeredDateTime, $this->messageQueue, $n2nContext);
		if (!$triggerInvestigator->checkAll()) {
			return null;
		}

		$this->triggerTracker->setLastTriggered($batchClassName, $now);
		return new BatchTriggerResult($registeredBatchObj->getObject(), $n2nContext);
	}

}