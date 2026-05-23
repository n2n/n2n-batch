<?php

namespace n2n\batch\mock;

use n2n\context\attribute\ThreadScoped;
use n2n\batch\attribute\BatchInterval;
use n2n\reflection\annotation\AnnoInit;
use n2n\batch\AnnoBatch;

#[ThreadScoped]
class BatchJobMock {
	private static function _annos(AnnoInit $ai): void {
		$ai->m('legacyInterval5m', new AnnoBatch(new \DateInterval('PT5M')));
	}

	public array $calledMethodNames = [];

	function _onTrigger(): void {
		$this->calledMethodNames[] = '_onTrigger';
	}

	function _onNewHour(): void {
		$this->calledMethodNames[] = '_onNewHour';
	}

	function _onNewDay(): void {
		$this->calledMethodNames[] = '_onNewDay';
	}

	function _onNewWeek(): void {
		$this->calledMethodNames[] = '_onNewWeek';
	}

	function _onNewMonth(): void {
		$this->calledMethodNames[] = '_onNewMonth';
	}

	function _onNewYear(): void {
		$this->calledMethodNames[] = '_onNewYear';
	}

	#[BatchInterval(new \DateInterval('PT5M'))]
	function interval5m(): void {
		$this->calledMethodNames[] = 'interval5m';
	}

	function legacyInterval5m(): void {
		$this->calledMethodNames[] = 'legacyInterval5m';
	}
}