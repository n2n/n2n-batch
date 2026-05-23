<?php
namespace n2n\batch\ext;

use PHPUnit\Framework\TestCase;
use n2n\test\TestEnv;
use n2n\batch\BatchClassRegistry;
use n2n\batch\mock\BatchJobMock;
use n2n\core\ext\BatchTriggerConfig;
use n2n\util\DateUtils;
use n2n\batch\mock\UnregisteredBatchJobMock;
use n2n\batch\mock\MessageMock;
use n2n\batch\mock\BatchMessageHandlerMock;
use n2n\batch\mock\FailingNoRequeueMessageMock;
use n2n\batch\BatchException;
use n2n\batch\message\MessageQueue;
use n2n\core\container\err\TransactionStateException;
use n2n\batch\mock\FailingRequeueMessageMock;

class BatchN2nExtensionTest extends TestCase {

	/**
	 * @throws \ReflectionException
	 */
	function setUp(): void {
		TestEnv::replaceN2nContext();

		$this->lookupMessageQueue()->clear();
	}

	/**
	 * @throws \ReflectionException
	 */
	private function lookupMessageQueue(): MessageQueue {
		$registry = TestEnv::lookup(BatchClassRegistry::class);
		return (new \ReflectionMethod($registry, 'createMessageQueue'))->invoke($registry);
	}

	function testConfig(): void {
		$this->assertSame([BatchJobMock::class, BatchMessageHandlerMock::class],
				TestEnv::lookup(BatchClassRegistry::class)->getBatchClassNames());
	}

	function testTrigger() {
		$results = TestEnv::getN2nContext()->getBatch()->trigger();
		$this->assertSame(
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek', '_onNewMonth', '_onNewYear', 'interval5m',
						'legacyInterval5m'],
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
	function testTriggerConfigCustom5mInterval() {
		$dateTime = new \DateTimeImmutable('2023-09-07 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime,
				$dateTime->sub(DateUtils::dateInterval(i: 5)), [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger', 'interval5m', 'legacyInterval5m'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

	/**
	 * @throws \DateInvalidOperationException
	 */
	function testTriggerConfigNewHour() {
		$dateTime = new \DateTimeImmutable('2023-09-07 12:12:12');

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime,
				$dateTime->sub(DateUtils::dateInterval(h: 1)), [BatchJobMock::class]));
		$this->assertSame(
				['_onTrigger', '_onNewHour', 'interval5m', 'legacyInterval5m'],
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
				['_onTrigger', '_onNewHour', '_onNewDay', 'interval5m', 'legacyInterval5m'],
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
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek', 'interval5m', 'legacyInterval5m'],
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
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek', '_onNewMonth', 'interval5m', 'legacyInterval5m'],
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
				['_onTrigger', '_onNewHour', '_onNewDay', '_onNewWeek', '_onNewMonth', '_onNewYear', 'interval5m',
						'legacyInterval5m'],
				$results[0]->batchJob->calledMethodNames);

		$results = TestEnv::getN2nContext()->getBatch()->trigger(new BatchTriggerConfig($dateTime));
		$this->assertSame(
				['_onTrigger'],
				$results[0]->batchJob->calledMethodNames);
	}

	function testDispatch() {
		$tx = TestEnv::createTransaction();
		TestEnv::getN2nContext()->getBatch()->dispatch(new MessageMock('holeradio1'));
		$tx->commit();

		$messageMocks = TestEnv::getN2nContext()->lookup(BatchMessageHandlerMock::class)->handledMessageMocks;
		$this->assertCount(1, $messageMocks);
		$this->assertSame('holeradio1', $messageMocks[0]->prop);
	}

	/**
	 * @throws \ReflectionException
	 */
	function testFailingRequeueDispatch() {
		$messageQueue  = $this->lookupMessageQueue();

		$this->assertNull($messageQueue->poll(FailingRequeueMessageMock::class));

		$tx = TestEnv::createTransaction();
		try {
			TestEnv::getN2nContext()->getBatch()->dispatch(new FailingRequeueMessageMock('holeradio2'));
			$tx->commit();
			$this->fail(TransactionStateException::class . ' expected');
		} catch (TransactionStateException $e) {
		}

		$this->assertNotNull($messageQueue->poll(FailingRequeueMessageMock::class));
	}

	/**
	 * @throws \ReflectionException
	 */
	function testFailingNoRequeueDispatch() {
		$messageQueue  = $this->lookupMessageQueue();

		$this->assertNull($messageQueue->poll(FailingNoRequeueMessageMock::class));

		$tx = TestEnv::createTransaction();
		try {
			TestEnv::getN2nContext()->getBatch()->dispatch(new FailingNoRequeueMessageMock('holeradio2'));
			$tx->commit();
			$this->fail(TransactionStateException::class . ' expected');
		} catch (TransactionStateException $e) {
		}

		$this->assertNull($messageQueue->poll(FailingNoRequeueMessageMock::class));
	}

	/**
	 * @throws \ReflectionException
	 */
	function testTriggerMessageHandler(): void {
		$registry = TestEnv::lookup(BatchClassRegistry::class);

		$messageQueue  = $this->lookupMessageQueue();

		$messageQueue
				->addAndPoll(MessageMock::class, new MessageMock('holeradio2'))
				->reject(true);

		$messageQueue
				->addAndPoll(MessageMock::class, new MessageMock('holeradio3'))
				->reject(true);

		$results = TestEnv::getN2nContext()->getBatch()->trigger();
		$this->assertCount(2, $results);
		$messageMocks = $results[1]->batchJob->handledMessageMocks;
		$this->assertCount(2, $messageMocks);
		$this->assertSame('holeradio2', $messageMocks[0]->prop);
		$this->assertSame('holeradio3', $messageMocks[1]->prop);
	}
}
