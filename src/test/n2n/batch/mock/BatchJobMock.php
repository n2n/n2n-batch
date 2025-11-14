<?php

namespace n2n\batch\mock;

use n2n\context\attribute\ThreadScoped;

#[ThreadScoped]
class BatchJobMock {

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
}