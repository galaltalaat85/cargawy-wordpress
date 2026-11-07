<?php
/**
 * Raised when a workflow transition is refused.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Workflow;

defined( 'ABSPATH' ) || exit;

/**
 * Transition refusal with a machine-readable reason.
 */
class TransitionException extends \RuntimeException {

	/**
	 * Reason code: not_found, invalid_transition, forbidden, storage_failed, or a guard code.
	 *
	 * @var string
	 */
	public $reason;

	/**
	 * Constructor.
	 *
	 * @param string  $reason    Reason code.
	 * @param string  $entity    Entity type.
	 * @param int $entity_id Entity ID.
	 * @param string  $from      From state.
	 * @param string  $to        To state.
	 */
	public function __construct( string $reason, string $entity, int $entity_id, string $from, string $to ) {
		$this->reason = $reason;
		parent::__construct( sprintf( '%s #%d: %s → %s refused (%s)', $entity, $entity_id, $from, $to, $reason ) );
	}
}
