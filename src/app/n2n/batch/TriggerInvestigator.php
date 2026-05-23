<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\batch;

use n2n\reflection\ReflectionContext;
use n2n\util\type\CastUtils;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\util\ex\ExUtils;
use n2n\batch\attribute\Batch;

class TriggerInvestigator {
	const ON_TRIGGER_METHOD = '_onTrigger';
	const NEW_HOUR_METHOD = '_onNewHour';
	const NEW_DAY_METHOD = '_onNewDay';
	const NEW_WEEK_METHOD = '_onNewWeek';
	const NEW_MONTH_METHOD = '_onNewMonth';
	const NEW_YEAR_METHOD = '_onNewYear';

	const LAST_TRIGGERED_ARG = 'lastTriggered';

	private \ReflectionClass $class;
	private MagicMethodInvoker $magicMethodInvoker;
	
	public function __construct(private object $batchJob, private \DateTimeImmutable $now,
			private ?\DateTimeImmutable $lastTriggeredDateTime, private $n2nContext) {
		$this->class = new \ReflectionClass($this->batchJob);
		$this->magicMethodInvoker = new MagicMethodInvoker($n2nContext);
	}

	function checkAll(): bool {
		$called = $this->check(TriggerInvestigator::ON_TRIGGER_METHOD, null);
		$called = $this->check(TriggerInvestigator::NEW_HOUR_METHOD, 'Y-m-d H') || $called;
		$called = $this->check(TriggerInvestigator::NEW_DAY_METHOD, 'Y-m-d') || $called;
		$called = $this->check(TriggerInvestigator::NEW_WEEK_METHOD, 'Y-m-W') || $called;
		$called = $this->check(TriggerInvestigator::NEW_MONTH_METHOD, 'Y-m') || $called;
		$called = $this->check(TriggerInvestigator::NEW_YEAR_METHOD, 'Y') || $called;
		return $this->checkIntervals() || $called;
	}
	
	private function check(string $methodName, ?string $dtCheckFormat = null): bool {
		if (!$this->class->hasMethod($methodName)) {
			return false;
		}

		if ($this->lastTriggeredDateTime === null || $dtCheckFormat === null
				|| $this->lastTriggeredDateTime->format($dtCheckFormat) != $this->now->format($dtCheckFormat)) {

			$method = ExUtils::try(fn () => $this->class->getMethod($methodName));
			$this->magicMethodInvoker->setParamValue(self::LAST_TRIGGERED_ARG, $this->lastTriggeredDateTime);
			$this->magicMethodInvoker->setMethod($method);
			$this->magicMethodInvoker->invoke($this->batchJob);
			return true;
		}

		return false;
	}
	
	private function checkIntervals() {
		$as = ReflectionContext::getAttributeSet($this->class);
		
		foreach ($as->getMethodAttributesByName(Batch::class) as $batchAttribute) {
			$method = $batchAttribute->getMethod();
			$batch = $batchAttribute->getInstance();
			assert($batch instanceof Batch);

			if ($this->lastTriggeredDateTime !== null) {
				$nextDt = $this->lastTriggeredDateTime->add($batch->interval);
				if ($nextDt > $this->now) {
					continue;
				}
			}
			
//			$method->setAccessible(true);
			$this->magicMethodInvoker->setParamValue(self::LAST_TRIGGERED_ARG, $this->lastTriggeredDateTime);
			$this->magicMethodInvoker->setMethod($method);
			$this->magicMethodInvoker->invoke($this->batchJob);
		}
	}
}