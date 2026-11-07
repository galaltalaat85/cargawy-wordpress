<?php
/**
 * Workflow engine, exercised through a small option-backed listing workflow.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

use CarGawy\Core\Workflow\StateMachine;
use CarGawy\Core\Workflow\TransitionException;

/**
 * Test double: states stored in an option; approver must differ from submitter.
 */
class Demo_Listing_Workflow extends StateMachine {

	public function entity_type(): string {
		return 'demo_listing';
	}

	protected function transitions(): array {
		return array(
			'draft'   => array( 'pending' => 'cg_submit_car' ),
			'pending' => array(
				'publish'     => 'cg_moderate_listings',
				'cg_rejected' => 'cg_moderate_listings',
			),
		);
	}

	protected function get_state( int $entity_id ): ?string {
		$all = get_option( 'cg_demo_states', array() );
		return $all[ $entity_id ]['state'] ?? null;
	}

	protected function set_state( int $entity_id, string $to, int $actor_id, array $context ): bool {
		$all                        = get_option( 'cg_demo_states', array() );
		$all[ $entity_id ]['state'] = $to;
		if ( 'pending' === $to ) {
			$all[ $entity_id ]['submitter'] = $actor_id;
		}
		return update_option( 'cg_demo_states', $all );
	}

	protected function guard( int $entity_id, string $from, string $to, int $actor_id, array $context ) {
		$all = get_option( 'cg_demo_states', array() );
		if ( 'publish' === $to && ( $all[ $entity_id ]['submitter'] ?? 0 ) === $actor_id ) {
			return 'self_approval';
		}
		return true;
	}

	public function seed( int $id, string $state ): void {
		$all        = get_option( 'cg_demo_states', array() );
		$all[ $id ] = array( 'state' => $state );
		update_option( 'cg_demo_states', $all );
	}
}

/**
 * @covers \CarGawy\Core\Workflow\StateMachine
 */
class StateMachineTest extends TestCase {

	private Demo_Listing_Workflow $wf;

	public function set_up() {
		parent::set_up();
		$this->wf = new Demo_Listing_Workflow();
		$this->wf->seed( 1, 'draft' );
	}

	private function reason( callable $attempt ): string {
		try {
			$attempt();
		} catch ( TransitionException $e ) {
			return $e->reason;
		}
		return 'no_exception';
	}

	public function test_allowed_transition_changes_state_audits_and_fires_hook() {
		$member = $this->user( 'cg_member' );
		$fired  = array();
		add_action(
			'cargawy/demo_listing/transition',
			function ( $id, $from, $to, $actor ) use ( &$fired ) {
				$fired = array( $id, $from, $to, $actor );
			},
			10,
			4
		);

		$from = $this->wf->transition( 1, 'pending', $member, 'submitted' );

		$this->assertSame( 'draft', $from );
		$this->assertSame( array( 1, 'draft', 'pending', $member ), $fired );
		$row = $this->last_audit();
		$this->assertSame( 'demo_listing', $row->entity_type );
		$this->assertSame( 'draft', $row->from_value );
		$this->assertSame( 'pending', $row->to_value );
		$this->assertSame( $member, (int) $row->actor_id );
	}

	public function test_undefined_transition_is_refused() {
		$admin = $this->user( 'administrator' );
		$this->assertSame( 'invalid_transition', $this->reason( fn() => $this->wf->transition( 1, 'publish', $admin ) ) );
	}

	public function test_missing_capability_is_refused_and_state_unchanged() {
		$this->wf->seed( 2, 'pending' );
		$member = $this->user( 'cg_member' );
		$this->assertSame( 'forbidden', $this->reason( fn() => $this->wf->transition( 2, 'publish', $member ) ) );
		$this->assertSame( array(), $this->wf->available_transitions( 2, $member ) );
		$this->assertSame( 'pending', get_option( 'cg_demo_states' )[2]['state'] );
	}

	public function test_guests_are_refused() {
		$this->assertSame( 'forbidden', $this->reason( fn() => $this->wf->transition( 1, 'pending', 0 ) ) );
	}

	public function test_unknown_entity_is_refused() {
		$this->assertSame( 'not_found', $this->reason( fn() => $this->wf->transition( 999, 'pending', $this->user( 'administrator' ) ) ) );
	}

	public function test_guard_blocks_self_approval_even_for_managers() {
		$manager = $this->user( 'cg_manager' );
		$this->wf->transition( 1, 'pending', $manager );
		$this->assertSame( 'self_approval', $this->reason( fn() => $this->wf->transition( 1, 'publish', $manager ) ) );
		$this->assertSame( array( 'cg_rejected' ), $this->wf->available_transitions( 1, $manager ) );

		$other = $this->user( 'cg_manager' );
		$this->assertSame( 'pending', $this->wf->transition( 1, 'publish', $other ) );
	}

	public function test_states_lists_every_state() {
		$states = $this->wf->states();
		sort( $states );
		$this->assertSame( array( 'cg_rejected', 'draft', 'pending', 'publish' ), $states );
	}
}
