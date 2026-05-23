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
namespace n2n\batch\interval;

use n2n\util\DateUtils;
use n2n\cache\CacheStore;
use n2n\cache\CharacteristicsList;

class TriggerTracker {	
	const LIMN_SEPARATOR = '::';

	
	public function __construct(private CacheStore $configSource) {

	}
	
	private function buildKey(string $batchJobClassName): string {
		return $batchJobClassName;
	}
	
	public function getLastTriggered(string $batchJobClassName): ?\DateTimeImmutable {
		$key = $this->buildKey($batchJobClassName);
		$timestamp = $this->configSource->get($key, new CharacteristicsList([]))?->getData();

		if (is_numeric($timestamp)) {
			return DateUtils::createDateTimeImmutableFromTimestamp((int) $timestamp);
		}
		
		return null;
	}
	
	public function setLastTriggered(string $batchJobClassName, \DateTimeImmutable $lastTriggered): void {
		$key = $this->buildKey($batchJobClassName);
		$this->configSource->store($key, new CharacteristicsList([]), $lastTriggered->getTimestamp());
	}
}
