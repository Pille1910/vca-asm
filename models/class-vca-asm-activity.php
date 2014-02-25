<?php

/**
 * VCA_ASM_Activity class.
 *
 * An instance of this class holds all information on a single activity
 *
 * @package VcA Activity & Supporter Management
 * @since 1.3
 */

if ( ! class_exists( 'VCA_ASM_Activity' ) ) :

class VCA_ASM_Activity {

	/**
	 * Class Properties
	 *
	 * @since 1.3
	 */
	private $default_args = array(
		'minimalistic' => false
	);
	private $args = array();

	public $id = 0;
	public $ID = 0;

	public $exists = false;
	public $is_activity = false;

	public $department = 'actions';

	public $post_object = object;
	public $name = '';
	public $meta = array();

	public $membership_required = false;

	public $start_app = 0;
	public $end_app = 0;
	public $start_act = 0;
	public $end_act = 0;
	public $upcoming = true;

	public $participants = array();
	public $participants_count = 0;
	public $waiting = array();
	public $waiting_count = 0;
	public $applicants = array();
	public $applicants_count = 0;

	public $participants_by_slots = array();
	public $participants_count_by_slots = array();
	public $waiting_by_slots = array();
	public $waiting_count_by_slots = array();
	public $applicants_by_slots = array();
	public $applicants_count_by_slots = array();

	public $participants_by_quota = array( 0 => array() );
	public $participants_count_by_quota = array( 0 => 0 );
	public $waiting_by_quota = array( 0 => array() );
	public $waiting_count_by_quota = array( 0 => 0 );
	public $applicants_by_quota = array( 0 => array() );
	public $applicants_count_by_quota = array( 0 => 0 );

	public $minimum_quotas = array();

	public $total_slots = 0;
	public $global_slots = 0;
	public $ctr_quotas = array();
	public $ctr_slots = array();
	public $ctr_quotas_switch = 'nay';
	public $cty_slots = array();
	public $ctr_cty_switch = array();
	public $slots = array();

	/**
	 * Checks whether an activity of id exists
	 *
	 * @since 1.3
	 * @access public
	 */
	public function is_activity( $id  ) {
		global $wpdb, $vca_asm_activities;

		$post_type = get_post_type( $id );

		if ( $post_type ) {
			$this->exists = true;
			if ( in_array( $post_type, $vca_asm_activities->activity_types ) ) {
				$this->is_activity = true;
				$this->gather_meta( $id );
			}
		}
	}

	/**
	 * Assigns values to class properties
	 *
	 * @since 1.3
	 * @access public
	 */
	public function gather_meta( $id ) {
		global $wpdb, $vca_asm_activities, $vca_asm_geography, $vca_asm_registrations, $vca_asm_utilities;

		$this->post_object = get_post( $id );
		$this->name = $this->post_object->post_title;
		$this->meta = get_post_meta( $id );

		$this->membership_required = ( 1 == get_post_meta( $id, 'membership_required', true ) ) ? true : false;

		$this->department = $vca_asm_activities->departments_by_activity[$this->post_object->post_type] ?
			$vca_asm_activities->departments_by_activity[$this->post_object->post_type] :
			'actions';

		$this->total_slots = get_post_meta( $id, 'total_slots', true );
		$this->global_slots = get_post_meta( $id, 'global_slots', true );
		$this->ctr_quotas_switch = get_post_meta( $id, 'ctr_quotas_switch', true );
		$this->ctr_quotas = get_post_meta( $id, 'ctr_quotas', true );
		$this->ctr_quotas = empty( $this->ctr_quotas ) ? array() : $this->ctr_quotas;
		$this->ctr_slots = get_post_meta( $id, 'ctr_slots', true );
		$this->ctr_slots = empty( $this->ctr_slots ) ? array() : $this->ctr_slots;
		$this->ctr_cty_switch = get_post_meta( $id, 'ctr_cty_switch', true );
		$this->ctr_cty_switch = empty( $this->ctr_cty_switch ) ? array() : $this->ctr_cty_switch;
		$this->cty_slots = get_post_meta( $id, 'cty_slots', true );
		$this->cty_slots = empty( $this->cty_slots ) ? array() : $this->cty_slots;
		$this->slots = array_merge( array( 0 => $this->global_slots ), $this->ctr_slots, $this->cty_slots );

		$this->start_app = get_post_meta( $id, 'start_app', true );
		$this->end_app = get_post_meta( $id, 'end_app', true );
		$this->start_act = get_post_meta( $id, 'start_act', true );
		$this->end_act = get_post_meta( $id, 'end_act', true );

		if ( time() > $this->end_act ) {
			$this->upcoming = false;
		}

		if ( true === $this->args['minimalistic'] ) {
			return;
		}

		if ( $this->upcoming ) {

			$this->participants_by_slots = $vca_asm_registrations->get_activity_participants( $id, array( 'by_contingent' => true ) );
			$this->waiting = $vca_asm_registrations->get_activity_waiting( $id );
			$this->applicants = $vca_asm_registrations->get_activity_applications( $id );

			foreach ( $this->participants_by_slots as $geo_id => $participants_bs ) {
				if ( ! array_key_exists( $geo_id, $this->participants_count_by_slots ) ) {
					$this->participants_count_by_slots[$geo_id] = 0;
				}
				if ( ! array_key_exists( $geo_id, $this->participants_by_quota ) ) {
					$this->participants_by_quota[$geo_id] = array();
				}
				if ( ! array_key_exists( $geo_id, $this->participants_count_by_quota ) ) {
					$this->participants_count_by_quota[$geo_id] = 0;
				}
				foreach ( $participants_bs as $participant ) {
					$this->participants[] = $participant;
					$this->participants_count++;
					$this->participants_count_by_slots[$geo_id]++;
					$this->participants_by_quota[$geo_id][] = $participant;
					$this->participants_count_by_quota[$geo_id]++;
					if ( $geo_id != 0 ) {
						$this->participants_by_quota[0][] = $participant;
						$this->participants_count_by_quota[0]++;
					}
					if ( $vca_asm_geography->is_city( $geo_id ) ) {
						$nation_query = $vca_asm_geography->get_ancestors( $geo_id, array(
							'data' => 'id',
							'format' => 'array',
							'type' => 'nation'
						));
						$nation = $nation_query[0];
						if ( ! array_key_exists( $nation, $this->participants_by_quota ) ) {
							$this->participants_by_quota[$nation] = array();
						}
						if ( ! array_key_exists( $nation, $this->participants_count_by_quota ) ) {
							$this->participants_count_by_quota[$nation] = 0;
						}
						$this->participants_by_quota[$nation][] = $participant;
						$this->participants_count_by_quota[$nation]++;
					}
				}
			}
			$this->minimum_quotas =& $this->participants_count_by_quota;

			foreach ( $this->waiting as $waiter /* LOL */ ) {
				$city_id = get_user_meta( $waiter, 'region', true );
				$nation_id = $vca_asm_geography->has_nation( $city_id );
				$level = 0;

				if ( array_key_exists( $city_id, $this->cty_slots ) ) {
					$level = $city_id;
					if ( ! array_key_exists( $city_id, $this->waiting_by_slots ) ) {
						$this->waiting_by_slots[$city_id] = array();
					}
					if ( ! array_key_exists( $city_id, $this->waiting_count_by_slots ) ) {
						$this->waiting_count_by_slots[$city_id] = 0;
					}
					if ( ! array_key_exists( $city_id, $this->waiting_by_quota ) ) {
						$this->waiting_by_quota[$city_id] = array();
					}
					if ( ! array_key_exists( $city_id, $this->waiting_count_by_quota ) ) {
						$this->waiting_count_by_quota[$city_id] = 0;
					}
					if ( ! array_key_exists( $nation_id, $this->waiting_by_quota ) ) {
						$this->waiting_by_quota[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->waiting_count_by_quota ) ) {
						$this->waiting_count_by_quota[$nation_id] = 0;
					}
				} elseif ( array_key_exists( $nation_id, $this->ctr_slots ) ) {
					$level = $nation_id;
					if ( ! array_key_exists( $nation_id, $this->waiting_by_slots ) ) {
						$this->waiting_by_slots[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->waiting_count_by_slots ) ) {
						$this->waiting_count_by_slots[$nation_id] = 0;
					}
					if ( ! array_key_exists( $nation_id, $this->waiting_by_quota ) ) {
						$this->waiting_by_quota[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->waiting_count_by_quota ) ) {
						$this->waiting_count_by_quota[$nation_id] = 0;
					}
				} else {
					$level = 0;
					if ( ! array_key_exists( $nation_id, $this->waiting_by_slots ) ) {
						$this->waiting_by_slots[0] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->waiting_count_by_slots ) ) {
						$this->waiting_count_by_slots[0] = 0;
					}
				}

				$this->waiting_count++;
				$this->waiting_by_slots[$level][] = $waiter;
				$this->waiting_count_by_slots[$level]++;
				$this->waiting_by_quota[$level][] = $waiter;
				$this->waiting_count_by_quota[$level]++;
				if ( $level === $city_id ) {
					$this->waiting_by_quota[$nation_id][] = $waiter;
					$this->waiting_count_by_quota[$nation_id]++;
				}
				if ( in_array( $level, array( $city_id, $nation_id ) ) ) {
					$this->waiting_by_quota[0][] = $waiter;
					$this->waiting_count_by_quota[0]++;
				}
			}

			foreach ( $this->applicants as $applicant ) {
				$city_id = get_user_meta( $applicant, 'region', true );
				$nation_id = $vca_asm_geography->has_nation( $city_id );
				$level = 0;

				if ( $city_id && array_key_exists( $city_id, $this->cty_slots ) ) {
					$level = $city_id;
					if ( ! array_key_exists( $city_id, $this->applicants_by_slots ) ) {
						$this->applicants_by_slots[$city_id] = array();
					}
					if ( ! array_key_exists( $city_id, $this->applicants_count_by_slots ) ) {
						$this->applicants_count_by_slots[$city_id] = 0;
					}
					if ( ! array_key_exists( $city_id, $this->applicants_by_quota ) ) {
						$this->applicants_by_quota[$city_id] = array();
					}
					if ( ! array_key_exists( $city_id, $this->applicants_count_by_quota ) ) {
						$this->applicants_count_by_quota[$city_id] = 0;
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_by_quota ) ) {
						$this->applicants_by_quota[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_count_by_quota ) ) {
						$this->applicants_count_by_quota[$nation_id] = 0;
					}
				} elseif ( $nation_id && array_key_exists( $nation_id, $this->ctr_slots ) ) {
					$level = $nation_id;
					if ( ! array_key_exists( $nation_id, $this->applicants_by_slots ) ) {
						$this->applicants_by_slots[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_count_by_slots ) ) {
						$this->applicants_count_by_slots[$nation_id] = 0;
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_by_quota ) ) {
						$this->applicants_by_quota[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_count_by_quota ) ) {
						$this->applicants_count_by_quota[$nation_id] = 0;
					}
				} else {
					$level = 0;
					if ( ! array_key_exists( 0, $this->applicants_by_slots ) ) {
						$this->applicants_by_slots[0] = array();
					}
					if ( ! array_key_exists( 0, $this->applicants_count_by_slots ) ) {
						$this->applicants_count_by_slots[0] = 0;
					}
				}

				$this->applicants_count++;
				$this->applicants_by_slots[$level][] = $applicant;
				$this->applicants_count_by_slots[$level]++;
				$this->applicants_by_quota[$level][] = $applicant;
				$this->applicants_count_by_quota[$level]++;
				if ( $level === $city_id ) {
					$this->applicants_by_quota[$nation_id][] = $applicant;
					$this->applicants_count_by_quota[$nation_id]++;
				}
				if ( in_array( $level, array( $city_id, $nation_id ) ) ) {
					$this->applicants_by_quota[0][] = $applicant;
					$this->applicants_count_by_quota[0]++;
				}
			}

		} else { // activity lies in the past

			$parts = $vca_asm_registrations->get_activity_participants( $id );
			if ( is_array( $parts ) && ! empty( $parts ) ) {
				foreach ( $parts as $part ) {
					$vca_asm_registrations->move_registration_to_old( $id, $part );
				}
			}
			$waits = $vca_asm_registrations->get_activity_waiting( $id );
			if ( is_array( $waits ) && ! empty( $waits ) ) {
				foreach ( $waits as $waiter ) {
					$vca_asm_registrations->move_application_to_old( $id, $waiter );
				}
			}
			$apps = $vca_asm_registrations->get_activity_applications( $id );
			if ( is_array( $apps ) && ! empty( $apps ) ) {
				foreach ( $apps as $app ) {
					$vca_asm_registrations->move_application_to_old( $id, $app );
				}
			}

			$this->participants_by_slots = $vca_asm_registrations->get_activity_participants_old( $id, array( 'by_contingent' => true ) );
			$this->applicants = $vca_asm_registrations->get_activity_applications_old( $id );

			foreach ( $this->participants_by_slots as $geo_id => $participants_bs ) {
				if ( ! array_key_exists( $geo_id, $this->participants_count_by_slots ) ) {
					$this->participants_count_by_slots[$geo_id] = 0;
				}
				if ( ! array_key_exists( $geo_id, $this->participants_by_quota ) ) {
					$this->participants_by_quota[$geo_id] = array();
				}
				if ( ! array_key_exists( $geo_id, $this->participants_count_by_quota ) ) {
					$this->participants_count_by_quota[$geo_id] = 0;
				}
				foreach ( $participants_bs as $participant ) {
					$this->participants[] = $participant;
					$this->participants_count++;
					$this->participants_count_by_slots[$geo_id]++;
					$this->participants_by_quota[$geo_id][] = $participant;
					$this->participants_count_by_quota[$geo_id]++;
					if ( $geo_id != 0 ) {
						$this->participants_by_quota[0][] = $participant;
						$this->participants_count_by_quota[0]++;
					}
					if ( $vca_asm_geography->is_city( $geo_id ) ) {
						$nation_query = $vca_asm_geography->get_ancestors( $geo_id, array(
							'data' => 'id',
							'format' => 'array',
							'type' => 'nation'
						));
						$nation = $nation_query[0];
						if ( ! array_key_exists( $nation, $this->participants_by_quota ) ) {
							$this->participants_by_quota[$nation] = array();
						}
						if ( ! array_key_exists( $nation, $this->participants_count_by_quota ) ) {
							$this->participants_count_by_quota[$nation] = 0;
						}
						$this->participants_by_quota[$nation][] = $participant;
						$this->participants_count_by_quota[$nation]++;
					}
				}
			}

			foreach ( $this->applicants as $applicant ) {
				$city_id = get_user_meta( $applicant, 'region', true );
				$nation_id = $vca_asm_geography->has_nation( $city_id );
				$level = 0;

				if ( array_key_exists( $city_id, $this->cty_slots ) ) {
					$level = $city_id;
					if ( ! array_key_exists( $city_id, $this->applicants_by_slots ) ) {
						$this->applicants_by_slots[$city_id] = array();
					}
					if ( ! array_key_exists( $city_id, $this->applicants_count_by_slots ) ) {
						$this->applicants_count_by_slots[$city_id] = 0;
					}
					if ( ! array_key_exists( $city_id, $this->applicants_by_quota ) ) {
						$this->applicants_by_quota[$city_id] = array();
					}
					if ( ! array_key_exists( $city_id, $this->applicants_count_by_quota ) ) {
						$this->applicants_count_by_quota[$city_id] = 0;
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_by_quota ) ) {
						$this->applicants_by_quota[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_count_by_quota ) ) {
						$this->applicants_count_by_quota[$nation_id] = 0;
					}
				} elseif ( array_key_exists( $nation_id, $this->ctr_slots ) ) {
					$level = $nation_id;
					if ( ! array_key_exists( $nation_id, $this->applicants_by_slots ) ) {
						$this->applicants_by_slots[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_count_by_slots ) ) {
						$this->applicants_count_by_slots[$nation_id] = 0;
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_by_quota ) ) {
						$this->applicants_by_quota[$nation_id] = array();
					}
					if ( ! array_key_exists( $nation_id, $this->applicants_count_by_quota ) ) {
						$this->applicants_count_by_quota[$nation_id] = 0;
					}
				} else {
					$level = 0;
					if ( ! array_key_exists( 0, $this->applicants_by_slots ) ) {
						$this->applicants_by_slots[0] = array();
					}
					if ( ! array_key_exists( 0, $this->applicants_count_by_slots ) ) {
						$this->applicants_count_by_slots[0] = 0;
					}
				}

				$this->applicants_count++;
				$this->applicants_by_slots[$level][] = $applicant;
				$this->applicants_count_by_slots[$level]++;
				$this->applicants_by_quota[$level][] = $applicant;
				$this->applicants_count_by_quota[$level]++;
				if ( $level === $city_id ) {
					$this->applicants_by_quota[$nation_id][] = $applicant;
					$this->applicants_count_by_quota[$nation_id]++;
				}
				if ( in_array( $level, array( $city_id, $nation_id ) ) ) {
					$this->applicants_by_quota[0][] = $applicant;
					$this->applicants_count_by_quota[0]++;
				}
			}
		}
	}

	/**
	 * Determines whether a supporter is eligible to this activity
	 *
	 * @param int $supporter_id
	 *
	 * @return mixed (bool) false if not, (int) quota (geo-unit) id if so
	 *
	 * @since 1.0
	 * @access public
	 */
	public function is_eligible( $supporter_id ) {
		global $vca_asm_geography;

		$membership_status = get_user_meta( $supporter_id, 'membership', true );

		if (
			! $this->membership_required ||
			2 == $membership_status
		) {
			$geo_id = get_user_meta( $supporter_id, 'region', true );
			$geo_id = is_numeric( $geo_id ) ? $geo_id : 0;
			if ( array_key_exists( $geo_id, $this->cty_slots ) && 0 < intval( $this->cty_slots[$geo_id] ) ) {
				return $geo_id;
			} else {
				$nation_query = $vca_asm_geography->get_ancestors( $geo_id, array(
					'data' => 'id',
					'format' => 'array',
					'type' => 'nation'
				));
				$ctr_id = isset( $nation_query[0] ) ? $nation_query[0] : 0;
				if ( array_key_exists( $ctr_id, $this->ctr_slots ) && 0 < intval( $this->ctr_slots[$ctr_id] ) ) {
					return $ctr_id;
				} else {
					if ( 0 < $this->global_slots ) {
						return 0;
					}
				}
			}
			return false;
		}
		return false;
	}

	/**
	 * Resets object
	 *
	 * @since 1.3
	 * @access public
	 */
	public function reset() {

		$this->membership_required = false;

		$this->participants = array();
		$this->participants_count = 0;
		$this->waiting = array();
		$this->waiting_count = 0;
		$this->applicants = array();
		$this->applicants_count = 0;

		$this->participants_by_slots = array();
		$this->participants_count_by_slots = array();
		$this->waiting_by_slots = array();
		$this->waiting_count_by_slots = array();
		$this->applicants_by_slots = array();
		$this->applicants_count_by_slots = array();

		$this->participants_by_quota = array( 0 => array() );
		$this->participants_count_by_quota = array( 0 => 0 );
		$this->waiting_by_quota = array( 0 => array() );
		$this->waiting_count_by_quota = array( 0 => 0 );
		$this->applicants_by_quota = array( 0 => array() );
		$this->applicants_count_by_quota = array( 0 => 0 );

		$this->minimum_quotas = array();

		$this->total_slots = 0;
		$this->global_slots = 0;
		$this->ctr_quotas = array();
		$this->ctr_slots = array();
		$this->ctr_quotas_switch = 'nay';
		$this->cty_slots = array();
		$this->ctr_cty_switch = array();
		$this->slots = array();

		$this->gather_meta( $this->id );
	}

	/**
	 * Dumps class properties as associative array
	 *
	 * @since 1.3
	 * @access public
	 */
	public function array_dump() {
		$dump = array(
			'id' => $this->id,
			'post_object' => $this->post_object,
			'meta' => $this->meta,
			'exists' => $this->exists,
			'is_activity' => $this->is_activity,
			'name' => $this->name,
			'membership_required' => $this->membership_required,
			'participants' => $this->participants,
			'participants_count' => $this->participants_count,
			'waiting' => $this->waiting,
			'waiting_count' => $this->waiting_count,
			'applicants' => $this->applicants,
			'applicants_count' => $this->applicants_count,
			'participants_by_slots' => $this->participants_by_slots,
			'participants_count_by_slots' => $this->participants_count_by_slots,
			'waiting_by_slots' => $this->waiting_by_slots,
			'waiting_count_by_slots' => $this->waiting_count_by_slots,
			'applicants_by_slots' => $this->applicants_by_slots,
			'applicants_count_by_slots' => $this->applicants_count_by_slots,
			'participants_by_quota' => $this->participants_by_quota,
			'participants_count_by_quota' => $this->participants_count_by_quota,
			'waiting_by_quota' => $this->waiting_by_quota,
			'waiting_count_by_quota' => $this->waiting_count_by_quota,
			'applicants_by_quota' => $this->applicants_by_quota,
			'applicants_count_by_quota' => $this->applicants_count_by_quota,
			'minimum_quotas' => $this->minimum_quotas,
			'total_slots' => $this->total_slots,
			'global_slots' => $this->global_slots,
			'ctr_quotas_switch' => $this->ctr_quotas_switch,
			'ctr_quotas' => $this->ctr_quotas,
			'ctr_slots' => $this->ctr_slots,
			'ctr_cty_switch' => $this->ctr_cty_switch,
			'cty_slots' => $this->cty_slots,
			'slots' => $this->slots
		);

		return $dump;
	}

	/**
	 * PHP4 style constructor
	 *
	 * @since 1.0
	 * @access public
	 */
	public function VCA_ASM_Activity( $id, $args = array() ) {
		$this->__construct( $id, $args = array() );
	}

	/**
	 * PHP5 style constructor
	 *
	 * @since 1.0
	 * @access public
	 */
	public function __construct( $id, $args = array() ) {
		$this->args = wp_parse_args( $args, $this->default_args );
		$this->id = intval( $id );
		$this->ID = $this->id;
		$this->is_activity( $this->id );
	}

} // class

endif; // class exists

?>