<?php

namespace n2n\batch\message;

use n2n\core\container\TransactionalResource;
use n2n\core\container\Transaction;
use n2n\core\container\N2nContext;
use n2n\batch\BatchJobClassAnalyzer;
use n2n\reflection\attribute\MethodAttribute;
use n2n\batch\attribute\BatchMessageClass;
use n2n\queue\PolledItemRef;
use n2n\batch\BatchException;
use n2n\util\ex\IllegalStateException;
use n2n\core\container\CommitListener;
use n2n\core\container\err\TransactionPhaseException;
use n2n\batch\LazyBatchObj;
use n2n\core\container\TransactionManager;

class AsyncMessageDispatcher implements TransactionalResource, CommitListener {
	private ?TransactionManager $tm = null;
	private bool $inTransaction = false;
	/**
	 * @var PendingAsyncMessageDispatch[]
	 */
	private array $pendingMessageDispatches = [];

	function __construct(private array $messageHandlerClassNames, private MessageQueue $messageQueue) {

	}

	/**
	 * @param object $message
	 * @param N2nContext $n2nContext
	 * @return BatchMessageClass[]
	 */
	function dispatchMessage(object $message, N2nContext $n2nContext): array {
		if (!$this->inTransaction) {
			throw new BatchException('Message can only be dispatched inside a transaction.');
		}

		$results = [];
		$messageClassName = get_class($message);
		foreach ($this->messageHandlerClassNames as $messageHandlerClassName) {
			$lazyBatchObj = new LazyBatchObj($messageHandlerClassName, $n2nContext);
			$messageClassAttribute = (new BatchJobClassAnalyzer($lazyBatchObj->getClass()))
					->findBatchInputAttribute($messageClassName);
			if ($messageClassAttribute === null) {
				continue;
			}

			$results[] = $this->dispatchMessageForHandler($lazyBatchObj, $messageClassAttribute, $message);
		}

		if (!empty($results)) {
			return $results;
		}

		throw new BatchException('No message handler registered which could handle messages of type: '
				. $messageClassName);
	}

	private function dispatchMessageForHandler(LazyBatchObj $lazyBatchObj, MethodAttribute $messageClassAttribute,
			object $message): BatchMessageDispatchResult {
		$messageClass = $messageClassAttribute->getInstance();
		assert($messageClass instanceof BatchMessageClass);

		if (!$messageClass->async) {
			$invoker = new MessageHandlerInvoker($lazyBatchObj);
			return new BatchMessageDispatchResult($lazyBatchObj, $messageClassAttribute, $message,
					$invoker->invokeSync($messageClassAttribute, $message));
		}

		$messageClassAttribute->getInstance();

		$this->pendingMessageDispatches[] = new PendingAsyncMessageDispatch($lazyBatchObj,
				$messageClassAttribute, $message);

		return new BatchMessageDispatchResult($lazyBatchObj, $messageClassAttribute, $message);
	}

	function release(): void {

	}

	public function beginTransaction(Transaction $transaction): void {
		$this->inTransaction = true;
	}

	public function prepareCommit(Transaction $transaction): void {
	}

	public function requestCommit(Transaction $transaction): void {
		foreach ($this->pendingMessageDispatches as $pendingMessageDispatch) {
			$pendingMessageDispatch->markAsStored(
					$this->messageQueue->addAndPoll(
							$pendingMessageDispatch->messageClassName,
							$pendingMessageDispatch->message));
		}
	}

	public function commit(Transaction $transaction): void {
		$this->tm->unregisterResource($this);
		$this->inTransaction = false;
	}

	public function rollBack(Transaction $transaction): void {
		$this->tm->unregisterResource($this);
		$this->inTransaction = false;
		$pendingMessageDispatches = $this->pendingMessageDispatches;
		$this->pendingMessageDispatches = [];
		foreach ($pendingMessageDispatches as $pendingMessageDispatch) {
			$pendingMessageDispatch->polledItemRef->reject(false);
		}
	}

	public function bindToTransactionManager(TransactionManager $tm): void {
		IllegalStateException::assertTrue($this->tm === null);
		$this->tm = $tm;
		$tm->registerResource($this);
		$tm->registerCommitListener($this);
	}

	private function handleMessages(): void {

	}

	function prePrepare(Transaction $transaction): void {
	}

	function postPrepare(Transaction $transaction): void {
	}

	public function preCommit(Transaction $transaction): void {
	}

	public function postCommit(Transaction $transaction): void {
	}

	public function preRollback(Transaction $transaction): void {
	}

	public function postRollback(Transaction $transaction): void {
	}

	public function postClose(Transaction $transaction): void {
		$this->tm->unregisterCommitListener($this);

		$pendingMessageDispatches = $this->pendingMessageDispatches;

		$this->pendingMessageDispatches = [];
		foreach ($pendingMessageDispatches as $pendingMessageDispatch) {
			$invoker = new MessageHandlerInvoker($pendingMessageDispatch->lazyBatchObj);
			$invoker->invokeAsync(
					$pendingMessageDispatch->methodAttribute,
					$pendingMessageDispatch->polledItemRef);
		}
	}

	public function postCorruptedState(?Transaction $transaction, TransactionPhaseException $e): void {
		$this->pendingMessageDispatches = [];
	}
}

