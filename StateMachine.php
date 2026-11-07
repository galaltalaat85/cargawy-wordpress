<?php
/**
 * Base class for every status workflow (listing, inspection, legal review,
 * dealer verification, lead).
 *
 * One entry point — transition() — checks the allowed transition, the
 * actor's capability, any extra guard (e.g. "approver must differ from
 * inspector"), writes the new state, writes the audit row and fires an
 * action. Concrete workflows only describe their states and storage.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Workflow;

// Exception messages are for logs and developers, never printed as HTML; callers escape when displaying.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

defined( 'ABSPATH' ) || exit;

/**
 * Abstract state machine.
 */
abstract class StateMachine {

	/**
	 * Entity type used in the audit log and hook names, e.g. "inspection".
	 */
	abstract public function entity_type(): string;

	/**
	 * Allowed transitions: from_state => [ to_state => required capability ].
	 *
	 * The capability is checked with user_can( $actor, $cap, $entity_id ), so
	 * meta capabilities with ownership rules work here.
	 *
	 * @return array<string, array<string, string>>
	 */
	abstract protected function transitions(): array;

	/**
	 * Read the current state, or null if the entity does not exist.
	 *
	 * @param int $entity_id Entity ID.
	 */
	abstract protected function get_state( int $entity_id ): ?string;

	/**
	 * Persist the new state.
	 *
	 * @param int $entity_id Entity ID.
	 * @param string  $to        New state.
	 * @param int $actor_id  Acting user.
	 * @param array   $context   Extra data passed to transition().
	 */
	abstract protected function set_state( int $entity_id, string $to, int $actor_id, array $context ): bool;

	/**
	 * Extra business rules. Return true to allow, or a reason code to refuse.
	 *
	 * @param int $entity_id Entity ID.
	 * @param string  $from      Current state.
	 * @param string  $to        Target state.
	 * @param int $actor_id  Acting user.
	 * @param array   $context   Extra data.
	 * @return true|string
	 */
	protected function guard( int $entity_id, string $from, string $to, int $actor_id, array $context ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- default allows everything; subclasses use the arguments.
		return true;
	}

	/**
	 * All states known to this workflow.
	 *
	 * @return string[]
	 */
	public function states(): array {
		$states = array();
		foreach ( $this->transitions() as $from => $targets ) {
			$states[] = $from;
			foreach ( array_keys( $targets ) as $to ) {
				$states[] = $to;
			}
		}
		return array_values( array_unique( $states ) );
	}

	/**
	 * Whether a transition is defined (ignores permissions).
	 *
	 * @param string $from From state.
	 * @param string $to   To state.
	 */
	public function is_defined( string $from, string $to ): bool {
		$map = $this->transitions();
		return isset( $map[ $from ][ $to ] );
	}

	/**
	 * Targets the actor may move the entity to right now.
	 *
	 * @param int $entity_id Entity ID.
	 * @param int $actor_id  Acting user.
	 * @return string[]
	 */
	public function available_transitions( int $entity_id, int $actor_id ): array {
		$from = $this->get_state( $entity_id );
		$map  = $this->transitions();
		if ( null === $from || empty( $map[ $from ] ) ) {
			return array();
		}
		$out = array();
		foreach ( $map[ $from ] as $to => $cap ) {
			if ( user_can( $actor_id, $cap, $entity_id ) && true === $this->guard( $entity_id, $from, $to, $actor_id, array() ) ) {
				$out[] = $to;
			}
		}
		return $out;
	}

	/**
	 * Move an entity to a new state.
	 *
	 * @param int      $entity_id Entity ID.
	 * @param string       $to        Target state.
	 * @param int|null $actor_id  Acting user; defaults to the current user.
	 * @param string       $note      Note stored in the audit log.
	 * @param array        $context   Extra data for guard() and set_state().
	 * @return string The previous state.
	 * @throws TransitionException When the entity is missing, the transition is undefined, forbidden or refused by a guard.
	 */
	public function transition( int $entity_id, string $to, ?int $actor_id = null, string $note = '', array $context = array() ): string {
		$actor_id = null === $actor_id ? get_current_user_id() : $actor_id;
		$type     = $this->entity_type();

		$from = $this->get_state( $entity_id );
		if ( null === $from ) {
			throw new TransitionException( 'not_found', $type, $entity_id, '', $to );
		}
		if ( ! $this->is_defined( $from, $to ) ) {
			throw new TransitionException( 'invalid_transition', $type, $entity_id, $from, $to );
		}
		$cap = $this->transitions()[ $from ][ $to ];
		if ( ! $actor_id || ! user_can( $actor_id, $cap, $entity_id ) ) {
			throw new TransitionException( 'forbidden', $type, $entity_id, $from, $to );
		}
		$guard = $this->guard( $entity_id, $from, $to, $actor_id, $context );
		if ( true !== $guard ) {
			throw new TransitionException( (string) $guard, $type, $entity_id, $from, $to );
		}
		if ( ! $this->set_state( $entity_id, $to, $actor_id, $context ) ) {
			throw new TransitionException( 'storage_failed', $type, $entity_id, $from, $to );
		}

		cargawy_audit( $type, $entity_id, 'transition', $from, $to, $note, $actor_id );

		/*
		 * Fires after any CarGawy workflow transition.
		 *
		 * Dynamic part: entity type, e.g. cargawy/inspection/transition.
		 */
		do_action( "cargawy/{$type}/transition", $entity_id, $from, $to, $actor_id, $context );

		return $from;
	}
}
