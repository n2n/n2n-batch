<?php
namespace n2n\batch\ext;

use PHPUnit\Framework\TestCase;
use n2n\test\TestEnv;
use n2n\batch\BatchJobRegistry;
use n2n\batch\mock\BatchJobMock;
use n2n\core\ext\BatchTriggerConfig;
use n2n\util\DateUtils;
use n2n\batch\mock\UnregisteredBatchJobMock;

class BatchN2nExtensionTest extends TestCase {

	function setUp(): void {
		TestEnv::replaceN2nContext();
	}

	function testConfig(): void {
		$this->assertSame([BatchJobMock::class], TestEnv::lookup(BatchJobRegistry::class)->getBatchJobClassNames());
	}

	function testTrigger() {
		$results = TestEnv::getN2nContext()->getBatch()->trigger();
		$this->assertSame(
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek', '_onNewMonth','_onNewYear'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger();
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);

	}

	function testTriggerConfig() {
		$dateTime = new \DateTimeImmutable('2023-09-07 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()
				->trigger(new BatchTriggerConfig($dateTime, $dateTime, [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

	function testEnsureBatchJobClassNameExists() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown batch job: n2n\batch\mock\UnregisteredBatchJobMock');

		TestEnv::getN2nContext()->getBatch()
				->trigger(new BatchTriggerConfig(filterBatchJobNames: [UnregisteredBatchJobMock::class]));

	}

	/**
	 * @throws \DateInvalidOperationException
	 */
	function testTriggerConfigNewHour() {
		$dateTime = new \DateTimeImmutable('2023-09-07 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime,
				$dateTime->sub(DateUtils::dateInterval(h: 1)), [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger', '_onNewHour'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

	/**
	 * @throws \DateInvalidOperationException
	 */
	function testTriggerConfigNewDay() {
		$dateTime = new \DateTimeImmutable('2023-09-07 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime,
				$dateTime->sub(DateUtils::dateInterval(d: 1)), [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger', '_onNewHour', '_onNewDay'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

	/**
	 * @throws \DateInvalidOperationException
	 */
	function testTriggerConfigNewWeek() {
		$dateTime = new \DateTimeImmutable('2023-09-08 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime,
				$dateTime->sub(DateUtils::dateInterval(d: 7)), [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

	/**
	 * @throws \DateInvalidOperationException
	 */
	function testTriggerConfigNewMonth() {
		$dateTime = new \DateTimeImmutable('2023-09-07 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime,
				$dateTime->sub(DateUtils::dateInterval(m: 1)), [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek', '_onNewMonth'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

	/**
	 * @throws \DateInvalidOperationException
	 */
	function testTriggerConfigNewYear() {
		$dateTime = new \DateTimeImmutable('2023-09-07 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime,
				$dateTime->sub(DateUtils::dateInterval(y: 1)), [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek', '_onNewMonth', '_onNewYear'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

}
