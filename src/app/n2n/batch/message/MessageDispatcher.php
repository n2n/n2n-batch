<?php

namespace n2n\batch\message;

use n2n\core\container\TransactionalResource;
use n2n\core\container\Transaction;
use n2n\core\container\N2nContext;
use n2n\batch\BatchJobClassAnalyzer;
use n2n\reflection\attribute\MethodAttribute;
use n2n\batch\attribute\MessageHandler;
use n2n\queue\PolledItemRef;
use n2n\batch\BatchException;
use n2n\util\ex\IllegalStateException;
use n2n\core\container\CommitListener;
use n2n\core\container\err\TransactionPhaseException;
use n2n\batch\LazyBatchObj;

class MessageDispatcher implements TransactionalResource, CommitListener {
	private bool $inTransaction = false;
	/**
	 * @var PendingMessageDispatch[]
	 */
	private array $pendingMessageDispatches = [];

	function __construct(private array $messageHandlerClassNames, private MessageQueue $messageQueue) {

	}

	function dispatchMessage(object $message, N2nContext $n2nContext): void {
		if (!$this->inTransaction) {
			throw new BatchException('Message can only be dispatched inside a transaction.');
		}

		$messageClassName = get_class($message);
		foreach ($this->messageHandlerClassNames as $messageHandlerClassName) {
			$lazyBatchObj = new LazyBatchObj($messageHandlerClassName, $n2nContext);
			$messageHandlerAttribute = (new BatchJobClassAnalyzer($lazyBatchObj->getClass()))
					->findBatchInputAttribute($messageClassName);
			if ($messageHandlerAttribute === null) {
				continue;
			}

			$this->pendingMessageDispatches[] = new PendingMessageDispatch($lazyBatchObj,
					$messageHandlerAttribute, $message, $n2nContext);
			return;
		}

		throw new BatchException('No message handler registered which could handle messages of type: '
				. $messageClassName);
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
		$this->inTransaction = false;
		foreach ($this->pendingMessageDispatches as $pendingMessageDispatch) {
			$pendingMessageDispatch->polledItemRef->reject(true);
		}
	}

	public function rollBack(Transaction $transaction): void {
		$this->inTransaction = false;
		foreach ($this->pendingMessageDispatches as $pendingMessageDispatch) {
			$pendingMessageDispatch->polledItemRef->reject(false);
		}
		$this->pendingMessageDispatches = [];
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
		foreach ($this->pendingMessageDispatches as $pendingMessageDispatch) {
			$invoker = new MessageHandlerInvoker($pendingMessageDispatch->n2nContext);
			$invoker->invoke($pendingMessageDispatch->lazyBatchObj->getObject(),
					$pendingMessageDispatch->methodAttribute->getMethod(),
					$pendingMessageDispatch->polledItemRef);
		}
	}

	public function postCorruptedState(?Transaction $transaction, TransactionPhaseException $e): void {
	}
}

class PendingMessageDispatch {

	function __construct(public readonly LazyBatchObj $lazyBatchObj,
			public readonly MethodAttribute $methodAttribute,
			public readonly object $message, public readonly N2nContext $n2nContext) {

	}

	public private(set) PolledItemRef $polledItemRef {
		get {
			IllegalStateException::assertTrue($this->polledItemRef !== null);
			return $this->polledItemRef;
		}
	}

	public string $messageClassName {
		get {
			$messageHandler = $this->methodAttribute->getInstance();
			assert($messageHandler instanceof MessageHandler);
			return $messageHandler->className;
		}
	}

	function markAsStored(PolledItemRef $polledItemRef): void {
		$this->polledItemRef = $polledItemRef;
	}
}