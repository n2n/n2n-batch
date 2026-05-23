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

use n2n\core\container\N2nContext;
use n2n\context\ThreadScoped;
use n2n\util\type\ArgUtils;
use n2n\batch\message\MessageDispatcher;
use n2n\batch\message\MessageQueue;
use n2n\queue\impl\QueueStorePools;
use n2n\core\VarStore;
use n2n\batch\interval\TriggerTracker;

/**
 * Manages and or organizes the execution of all active batch jobs.
 *
 */
class BatchClassRegistry implements ThreadScoped {
	
	function __construct(private N2nContext $n2nContext, private array $batchJobClassNames = []) {
		ArgUtils::valArray($this->batchJobClassNames, 'string');
	}

	public function registerBatchLookupClassName(string $className): void {
		$this->batchJobClassNames[] = $className;
	}

	/**
	 * @return string[];
	 */
	function getBatchClassNames(): array {
		return $this->batchJobClassNames;
	}

	/**
	 * @deprecated use {@link self::registerBatchLookupClassName()}
	 */
	public function registerBatchJobLookupId(string $lookupId): void {
		$this->registerBatchLookupClassName($lookupId);
	}

	function createBatchWorker(): BatchWorker {
		return new BatchWorker($this->batchJobClassNames, $this->createTriggerTracker(),
				$this->createMessageQueue());
	}

	private function createTriggerTracker(): TriggerTracker {
		return new TriggerTracker($this->n2nContext->getAppCache()->lookupCacheStore(TriggerTracker::class, true));
	}


	private function createMessageQueue(): MessageQueue {
		return new MessageQueue(QueueStorePools::file($this->n2nContext->getVarStore()
				->requestDirFsPath(VarStore::CATEGORY_TMP, MessageQueue::class)));
	}

	function createMessageDispatcher(): MessageDispatcher {
		return new MessageDispatcher($this->batchJobClassNames, $this->createMessageQueue(),
				$this->n2nContext);
	}

	/**
	 * 
	 */
//	private function triggerRequestListeners() {
//		foreach ($this->batchJobLookupIds as $batchControllerClass) {
//			if (!$batchControllerClass->implementsInterface('n2n\batch\RequestListener')) continue;
//
//			$requestListener = $this->usableManager->lookupByClass($batchControllerClass);
//			$requestListener->onRequest();
//		}
//	}
	
// 	private function readLastTriggeredTimestamps(FileResourceStream $fileStream) {
// 		$lastTriggeredTimestamps = null;
		
// 		try {
// 			$lastTriggeredTimestamps = StringUtils::unserialize($fileStream->read());
// 		} catch (UnserializationFailedException $e) {
// 		}
		
// 		if (!is_array($lastTriggeredTimestamps) || !isset($lastTriggeredTimestamps[self::DATETIME_TRIGGERED_KEY])
// 				|| !isset($lastTriggeredTimestamps[self::DATEINTERVAL_TRIGGERED_KEY])
// 				|| !is_array($lastTriggeredTimestamps[self::DATEINTERVAL_TRIGGERED_KEY])) {
// 			$lastTriggeredTimestamps = array(self::DATETIME_TRIGGERED_KEY => null, self::DATEINTERVAL_TRIGGERED_KEY => array());
// 		}
		
// 		return $lastTriggeredTimestamps;
// 	}
	
// 	private function writeLastTriggeredTimestamps(FileResourceStream $fileStream, array $lastTriggeredTimestamps) {
// 		$fileStream->truncate();
// 		$fileStream->write(serialize($lastTriggeredTimestamps));
// 	}
// 	/**
// 	 * 
// 	 * @param \DateTime $now
// 	 * @param array $lastTriggeredTimestamps
// 	 */				 
// 	private function checkDateIntervalListeners(\DateTime $now, array &$lastTriggeredTimestamps) {
// 		foreach ($this->batchJobLookupIds as $batchControllerClass) {
// 			if (!$batchControllerClass->implementsInterface('n2n\batch\DateIntervalListener')) {
// 				continue;
// 			}
			
// 			if (isset($lastTriggeredTimestamps[$batchControllerClass->getName()]) 
// 					&& !$this->isDateIntervalPassed($batchControllerClass, $now, 
// 							$lastTriggeredTimestamps[$batchControllerClass->getName()])) {
// 				continue;
// 			}
			
// 			$this->usableManager->lookupByClass($batchControllerClass)->timePassed();
// 			$lastTriggeredTimestamps[$batchControllerClass->getName()] = $now->getTimestamp();
// 		}
// 	}
// 	/**
// 	 * 
// 	 * @param unknown_type $batchControllerClass
// 	 * @param \DateTime $now
// 	 * @param unknown_type $lastTriggeredTimestamp
// 	 * @return bool
// 	 * @throws ControllerErrorException
// 	 */
// 	private function isDateIntervalPassed($batchControllerClass, \DateTime $now, $lastTriggeredTimestamp) {
// 		throw new NotYetImplementedException();
		
// // 		$annotationAnalyzer = ReflectionContext::getAnnotationAnalyzer($batchControllerClass);
// // 		$dateInterval = null;
		
// // 		try {
// // 			$intervalSpec = $annotationAnalyzer->getClassAnnotationValue(self::DATEINTERVAL_ANNOTATION_NAME);
// // 			$dateInterval = DateUtils::createDateInterval($intervalSpec);
// // 		} catch (AnnotationNotFoundException $e) {
// // 			throw new ControllerErrorException(
// // 					SysTextUtils::get('n2n_error_cont_dateinterval_listeners_requires_annotation',
// // 							array('class' => $batchControllerClass->getName(), 'annotation' => self::DATEINTERVAL_ANNOTATION_NAME)),
// // 					0, E_USER_ERROR, $batchControllerClass->getFileName(), $batchControllerClass->getStartLine(), null, null, $e);
// // 		} catch (DateIntervalParsingFailedException $e) {
// // 			throw new ControllerErrorException(
// // 					SysTextUtils::get('n2n_error_cont_invalid_annotated_dateinterval',
// // 							array('class' => $batchControllerClass->getName(), 'annotation' => self::DATEINTERVAL_ANNOTATION_NAME, 'dateInterval' => $intervalSpec)),
// // 					0, E_USER_ERROR, $batchControllerClass->getFileName(), $batchControllerClass->getStartLine(), null, null, $e);
// // 		}
		
// // 		$lastMod = null;
// // 		try {
// // 			$lastMod = DateUtils::createDateTime('@' . $lastTriggeredTimestamp);
// // 		} catch (DateTimeParsingFailedException $e) {
// // 			return true;
// // 		}
		
// // 		if (is_null($lastMod) && $lastMod->add($dateInterval) < $now) {
// // 			return true;
// // 		}
		
// // 		return false;
// 	}
//	/**
//	 *
//	 * @param \DateTime $now
//	 * @param int $lastTriggeredTimestamp
//	 */
//	private function checkDateTimeListeners(\DateTime $now, &$lastTriggeredTimestamp) {
//		$lastMod = null;
//		try {
//			$lastMod = DateUtils::createDateTime('@' . $lastTriggeredTimestamp);
//			$lastMod->setTimezone($now->getTimezone());
//		} catch (DateParseException $e) { }
//
//		if (is_null($lastMod) || $lastMod->format('Y') != $now->format('Y')) {
//			$this->triggerNewHourListeners();
//			$this->triggerNewDayListeners();
//			$this->triggerNewMonthListeners();
//			$this->triggerNewYearListeners();
//			$lastTriggeredTimestamp = $now->getTimestamp();
//			return true;
//		}
//
//		if ($lastMod->format('m') != $now->format('m')) {
//			$this->triggerNewHourListeners();
//			$this->triggerNewDayListeners();
//			$this->triggerNewMonthListeners();
//			$lastTriggeredTimestamp = $now->getTimestamp();
//			return true;
//		}
//
//		if ($lastMod->format('d') != $now->format('d')) {
//			$this->triggerNewHourListeners();
//			$this->triggerNewDayListeners();
//			$lastTriggeredTimestamp = $now->getTimestamp();
//			return true;
//		}
//
//		if ($lastMod->format('H') != $now->format('H')) {
//			$this->triggerNewHourListeners();
//			$lastTriggeredTimestamp = $now->getTimestamp();
//			return true;
//		}
//
//		return false;
//	}
//	/**
//	 *
//	 */
//	private function triggerNewHourListeners() {
//		foreach ($this->batchJobLookupIds as $batchControllerClass) {
//			if (!$batchControllerClass->implementsInterface('n2n\batch\NewHourListener')) continue;
//
//			$this->usableManager->lookupByClass($batchControllerClass)->onNewHour();
//		}
//	}
//	/**
//	 *
//	 */
//	private function triggerNewDayListeners() {
//		foreach ($this->batchJobLookupIds as $batchControllerClass) {
//			if (!$batchControllerClass->implementsInterface('n2n\batch\NewDayListener')) continue;
//
//			$this->usableManager->lookupByClass($batchControllerClass)->onNewDay();
//		}
//	}
//	/**
//	 *
//	 */
//	private function triggerNewMonthListeners() {
//		foreach ($this->batchJobLookupIds as $batchControllerClass) {
//			if (!$batchControllerClass->implementsInterface('n2n\batch\NewMonthListener')) continue;
//
//			$this->usableManager->lookupByClass($batchControllerClass)->onNewMonth();
//		}
//	}
//	/**
//	 *
//	 */
//	private function triggerNewYearListeners() {
//		foreach ($this->batchJobLookupIds as $batchControllerClass) {
//			if (!$batchControllerClass->implementsInterface('n2n\batch\NewYearListener')) continue;
//
//			$this->usableManager->lookupByClass($batchControllerClass)->onNewYear();
//		}
//	}
//	/**
//	 *
//	 * @param string $path
//	 * @param string $pattern
//	 * @return bool
//	 */
//	public function check($path, $pattern) {
//		$now = new \DateTime();
//
//		$mTime = null;
//		if (is_file($path)) {
//			$mTime = new \DateTime('@' . IoUtils::filemtime($path));
//			if ($now->format($pattern) <= $mTime->format($pattern)) {
//				return false;
//			}
//		}
//
//		$fileStream = IoUtils::createSafeFileStream($path);
//
//		if (isset($mTime)) {
//			$tsMTime = new \DateTime('@' . $fileStream->read());
//			if ($now->format($pattern) <= $tsMTime->format($pattern)) {
//				return false;
//			}
//		}
//
//		$fileStream->truncate();
//		$fileStream->write($now->getTimestamp());
//		$fileStream->close();
//
//		return true;
//	}
//	/**
//	 *
//	 * @param \ReflectionClass $class
//	 * @return bool
//	 */
//	public function isDateTimeListenerClass(\ReflectionClass $class) {
//		foreach (self::getDateTimeListenerInterfaceNames() as $interfaceName) {
//			if ($class->implementsInterface($interfaceName)) return true;
//		}
//
//		return false;
//	}
//	/**
//	 *
//	 * @return array
//	 */
//	public static function getDateTimeListenerInterfaceNames() {
//		return array('n2n\batch\NewYearListener', 'n2n\batch\NewMonthListener',
//				'n2n\batch\NewDayListener', 'n2n\batch\NewHourListener');
//	}
}

//class InvalidListenerClassException {
//
//}
