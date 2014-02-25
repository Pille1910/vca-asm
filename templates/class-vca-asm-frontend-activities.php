<?php

/**
 * VCA_ASM_Frontend_Activities class
 *
 * This class contains properties and methods
 * to display activities in the frontend.
 *
 * @package VcA Activity & Supporter Management
 * @since 1.3
 */

if ( ! class_exists( 'VCA_ASM_Frontend_Activities' ) ) :

class VCA_ASM_Frontend_Activities {

	/**
	 * Class Properties
	 *
	 * @since 1.3
	 */
	private $default_args = array(
		'echo' => false,
		'with_filter' => false,
		'eligibility_check' => false,
		'action' => false,
		'list_class' => '',
		'pre_text' => '',
		'minimalistic' => false
	);
	private $args = array();
	private $activities = array();

	private $nations = array();
	private $months = array();

	/**
	 * PHP4 style constructor
	 *
	 * @since 1.3
	 * @access public
	 */
	public function VCA_ASM_Frontend_Activities( $activities, $args = array() ) {
		$this->__construct( $activities, $args );
	}

	/**
	 * PHP5 style constructor
	 *
	 * @since 1.3
	 * @access public
	 */
	public function __construct( $activities, $args = array() ) {
		$this->activities = $activities;
		$this->args = wp_parse_args( $args, $this->default_args );
	}

	/**
	 * Constructs output HTML,
	 * echoes or returns it
	 *
	 * @since 1.3
	 * @access public
	 */
	public function output() {
		global $current_user, $vca_asm_geography, $vca_asm_utilities;
		get_currentuserinfo();

		extract( $this->args );

		if( empty( $this->activities ) ) {

			return;

		} elseif ( ! $minimalistic ) {

			$mnth_qs = '';
			$nat_qs = '';
			if ( $with_filter ) {
				if ( isset( $_GET['mnth'] ) && is_numeric( $_GET['mnth'] ) ) {
					$mnth_filter = $_GET['mnth'];
					$mnth_qs = '&mnth=' . $mnth_filter;
				} else {
					$mnth_filter = date( 'n' );
					$mnth_qs = '&mnth=' . $mnth_filter;
				}
				if ( isset( $_GET['ctr'] ) && is_numeric( $_GET['ctr'] ) ) {
					$nat_filter = $_GET['ctr'];
					$nat_qs = '&ctr=' . $nat_filter;
				} else {
					if ( ! empty( $_SERVER['SERVER_NAME'] ) ) {
						$domain = $_SERVER['SERVER_NAME'];
					} elseif ( ! isset( $domain ) && ! empty( $_SERVER['HTTP_HOST'] ) ) {
						$domain = $_SERVER['HTTP_HOST'];
					}

					if ( isset( $domain ) && 'pool.vivaconagua.ch' === $domain ) {
						$nat_filter = 42;
					} else {
						$nat_filter = 40;
					}
					$nat_qs = '&ctr=' . $nat_filter;
				}
			}

			$user_city = get_user_meta( $current_user->ID, 'city', true );
			$user_mem_status = get_user_meta( $current_user->ID, 'membership', true );
			$user_lang = get_user_meta( $current_user->ID, 'pool_lang', true );

			while ( $this->activities->have_posts() ) : $this->activities->the_post();

				$cur_month = date( 'n', intval( get_post_meta( get_the_ID(), 'start_act', true ) ) );
				if( ! in_array( $cur_month, $this->months ) ) {
					$this->months[] = $cur_month;
				}

				$cur_cty = get_post_meta( get_the_ID(), 'city', true );
				if ( is_numeric( $cur_cty ) ) {
					$cur_nat = $vca_asm_geography->has_nation( $cur_cty );
				} else {
					$cur_nat = get_post_meta( get_the_ID(), 'nation', true );
				}
				if( ! empty( $cur_nat ) && ! in_array( $cur_nat, $this->nations ) ) {
					$this->nations[] = $cur_nat;
				}

			endwhile;
			wp_reset_postdata();

			if ( ! isset( $nat_filter ) || ! in_array( $nat_filter, $this->nations ) ) {
				$nat_filter = 0;
				$nat_qs = '&ctr=' . $nat_filter;
			}

			if ( ! isset( $mnth_filter ) || ! in_array( $mnth_filter, $this->months ) ) {
				$mnth_filter = 0;
				$mnth_qs = '&mnth=' . $mnth_filter;
			}

			$output = '';

			if ( ! empty( $pre_text ) ) {
				$output .= '<p class="message">' .
					$pre_text .
					'</p>';
			}

			/* list & loop through posts (activities) */
			$output .=  '<ul class="' . $list_class . '">';

			while ( $this->activities->have_posts() ) : $this->activities->the_post();

				$the_activity = new VCA_ASM_Activity( get_the_ID() );
				$eligible_quota = $the_activity->is_eligible( $current_user->ID );

				if ( true === $eligibility_check && ! is_numeric( $eligible_quota ) ) {
					continue;
				}

				$cur_month = date( 'n', intval( get_post_meta( get_the_ID(), 'start_act', true ) ) );

				$cur_cty = get_post_meta( get_the_ID(), 'city', true );
				if ( is_numeric( $cur_cty ) ) {
					$cur_nat = $vca_asm_geography->has_nation( $cur_cty );
				} else {
					$cur_nat = get_post_meta( get_the_ID(), 'nation', true );
				}

				if (
					( ! isset( $mnth_filter ) || 0 == $mnth_filter || $mnth_filter == $cur_month ) &&
					( ! isset( $nat_filter ) || 0 == $nat_filter || $nat_filter == $cur_nat )
				) {

					if ( 'en' === $user_lang ) {
						$start_act_string = strftime( '%A, %e/%m/%Y, %H:%M', $the_activity->start_act );
						$end_act_string = strftime( '%A, %e/%m/%Y, %H:%M', $the_activity->end_act );
						$start_app_string = strftime( '%A, %e/%m/%Y', $the_activity->start_app );
						$end_app_string = strftime( '%A, %e/%m/%Y', $the_activity->end_app );
					} else {
						$start_act_string = strftime( '%A, %e.%m.%Y, %H:%M', $the_activity->start_act );
						$end_act_string = strftime( '%A, %e.%m.%Y, %H:%M', $the_activity->end_act );
						$start_app_string = strftime( '%A, %e.%m.%Y', $the_activity->start_app );
						$end_app_string = strftime( '%A, %e.%m.%Y', $the_activity->end_app );
					}

					$output .= '<li class="activity toggle-wrapper">' .

						'<img class="activity-icon" alt="' . $the_activity->nice_type . '" src="' . $the_activity->icon_url . '" />' .

						'<h4><a title="' . __( 'Single view', 'vca-asm' ) . '" href="' . get_permalink() . '">' . get_the_title() . '</a></h4>' .
						'<p class="type">' . $the_activity->nice_type . '</p>' .

						'<table class="meta-table">' .
							'<tr>' .
								'<td><p class="label">' . __( 'Timeframe', 'vca-asm' ) . '</p>' .
								'<p class="metadata">';

								if ( strftime( '%e.%m.%Y', $the_activity->start_act ) === strftime( '%e.%m.%Y', $the_activity->end_act ) ) {
									$output .= $start_act_string .
										' ' . __( 'until', 'vca-asm' ) . ' ' .
										strftime( '%H:%M', $the_activity->end_act );
								} else {
									$output .= $start_act_string .
										' ' . __( 'until', 'vca-asm' ) . ' ' .
										$end_act_string;
								}

					$output .= '</p></td>' .
							'</tr>' .
							'<tr>' .
								'<td><p class="label">' . __( 'Location', 'vca-asm' ) . '</p>' .
								'<p class="metadata">' .
									get_post_meta( get_the_ID(), 'location', true ) .
								'</p></td>' .
							'</tr>' .
						'</table>';

					$output .= '<div class="toggle-element"><div class="measuring-wrapper">' .
						'<table class="meta-table">' .
							'<tr>' .
								'<td><p class="label">' . __( 'Application Deadline', 'vca-asm' ) . '</p>' .
								'<p class="metadata">' .
									$end_app_string .
								'</p></td>' .
							'</tr>' .
							'<tr>' .
								'<td><p class="label">' . _x( 'available Slots', 'i.e. max. participants', 'vca-asm' ) . '</p>' .
								'<p class="metadata">' .
									get_post_meta( get_the_ID(), 'total_slots', true ) .
								'</p></td>' .
							'</tr>' .
						'</table>';

					$subput = '';

					$tools_enc = get_post_meta( get_the_ID(), 'tools', true );
					$special_desc =  get_post_meta( get_the_ID(), 'special', true );

					if( ! empty( $tools_enc ) ) {
						$tools = array();
						if( in_array( '1', $tools_enc ) ) {
							$tools[] = _x( 'Cups', 'VcA Tools', 'vca-asm' );
						}
						if( in_array( '2', $tools_enc ) ) {
							$tools[] = _x( 'Guest List', 'VcA Tools', 'vca-asm' );
						}
						if( in_array( '3', $tools_enc ) ) {
							$tools[] = _x( 'Info Counter', 'VcA Tools', 'vca-asm' );
						}
						if( in_array( '4', $tools_enc ) ) {
							$tools[] = _x( 'Water Bottles', 'VcA Tools', 'vca-asm' );
						}
						if( in_array( '5', $tools_enc ) ) {
							if( isset( $special_desc ) && ! empty( $special_desc ) ) {
								$tools[] = $special_desc;
							} else {
								$tools[] = _x( 'Special', 'VcA Tools', 'vca-asm' );
							}
						}
						$tools = implode( ', ', $tools );

						$subput .= '<tr>' .
								'<td><p class="label">' . __( 'VcA Activities', 'vca-asm' ) . '</p>' .
								'<p class="metadata">' .
									$tools .
								'</p></td>' .
							'</tr>';
					}

					$site = get_post_meta( get_the_ID(), 'website', true );
					if ( ! empty( $site ) ) {
						$subput .= '<tr>' .
								'<td><p class="label">' . __( 'Website', 'vca-asm' ) . '</p>' .
								'<p class="metadata">' .
									$vca_asm_utilities->urls_to_links( $site ) .
								'</p></td>' .
							'</tr>';
					}

					$directions = get_post_meta( get_the_ID(), 'directions', true );
					if ( ! empty( $directions ) ) {
						$subput .= '<tr>' .
								'<td><p class="label">' . __( 'Directions', 'vca-asm' ) . '</p>' .
								'<p class="metadata">' .
									preg_replace( '#(<br */?>\s*){2,}#i', '<br><br>' , preg_replace( '/[\r|\n]/', '<br>' , $vca_asm_utilities->urls_to_links( $directions ) ) ) .
								'</p></td>' .
							'</tr>';
					}

					$notes = get_post_meta( get_the_ID(), 'notes', true );
					if ( ! empty( $notes ) ) {
						$subput .= '<tr>' .
								'<td><p class="label">' . __( 'additional Notes', 'vca-asm' ) . '</p>' .
								'<p class="metadata">' .
									preg_replace( '#(<br */?>\s*){2,}#i', '<br><br>' , preg_replace( '/[\r|\n]/', '<br>' , $vca_asm_utilities->urls_to_links( $notes ) ) ) .
								'</p></td>' .
							'</tr>';
					}

					if ( ! empty( $subput ) ) {
						$output .= '<h5>' . __( 'Further Info', 'vca-asm' ) . '</h5>' .
							'<table class="meta-table">' . $subput . '</table>';
						$subput = '';
					}

					if( ! empty( $action ) && 'app' === $action ) {

						$output .= '<h5>' . __( 'Participate', 'vca-asm' ) . '</h5>' .
							'<form method="post" action="">' .
							'<input type="hidden" name="unique_id" value="[' . md5( uniqid() ) . ']">' .
							'<input type="hidden" name="todo" id="todo" value="apply" />' .
							'<input type="hidden" name="activity" id="activity" value="' . get_the_ID() . '" />' .
							'<div class="form-row">' .
								'<div class="no-js-toggle">' .
									'<textarea name="notes" id="notes" rows="4"></textarea>' .
									'<br /><span class="description">' .
										_x( 'If you wish to send a message with your application, do so here.', 'Frontend: Application Process', 'vca-asm' ) .
									'</span>' .
								'</div>' .
								'<div class="js-toggle">' .
									'<textarea name="notes" id="notes" class="textarea-hint" rows="5">' .
										_x( 'If you wish to send a message with your application, do so here.', 'Frontend: Application Process', 'vca-asm' ) .
										"\n\n" .
										_x( "For instance if you're applying with a friend, cannot reach on time, or the like.", 'Frontend: Application Process', 'vca-asm' ) .
									'</textarea>' .
								'</div>' .
							'</div><div class="form-row">' .
								'<input type="submit" id="submit_form" name="submit_form" value="' . __( 'Apply', 'vca-asm' ) . '" />' .
							'</div></form>';
					}

					if( ! empty( $action ) && 'rev_app' === $action ) {

						$output .= '<form method="post" action="">' .
							'<input type="hidden" name="todo" id="todo" value="revoke_app" />' .
							'<input type="hidden" name="activity" id="activity" value="' . get_the_ID() . '" />' .
							'<div class="form-row">' .
								'<input type="submit" id="submit_form" name="submit_form" value="' . __( 'Revoke Application', 'vca-asm' ) . '" />' .
							'</div></form>';

					}

					$output .= '</div></div><div class="toggle-arrows-wrap">' .
						'<a class="toggle-link toggle-arrows toggle-arrows-more" title="' . __( 'Toggle additional info', 'vca-asm' ) . '" ' . 'href="#">' .
							'<img alt="' . __( 'More/Less', 'vca-asm' ) . '"src="' .
								get_bloginfo( 'template_url' ) . '/images/arrows.png" />' .
						'</a></div>';

					$output .= '</li>';
				}

			endwhile;
			wp_reset_postdata();

			$output .= '</ul>';

			if ( count( $this->months ) > 1 || count( $this->nations ) > 1 ) {
				$selector = '<div class="island options-island">' .
						'<h3>' . __( 'Filter Activitites', 'vca-asm' ) . '</h3>' .
						'<table class="options-table">';

				if ( count( $this->months ) > 1 ) {
					$selector .= '<tr><td><p class="label">' . __( 'Months', 'vca-asm' ) . '</p>' .
						'<p class="metadata"><a ';
							if ( ! isset( $mnth_filter ) || 0 == $mnth_filter ) {
								$selector .= 'class="active-option" ';
							}
							$selector .= 'title="' . __( 'All months', 'vca-asm' ) . '" href="' . get_bloginfo( 'url' ) . '?mnth=0' . $nat_qs . '">' . __( 'All', 'vca-asm' ) . '</a> | ';
					$i = 0;
					foreach ( $this->months as $month ) {
						$selector .= '<a ';
						if ( isset( $mnth_filter ) && $month == $mnth_filter ) {
							$selector .= 'class="active-option" ';
						}
						$selector .= 'title="' . __( 'Filter by month', 'vca-asm' ) . '" href="' . get_bloginfo( 'url' ) . '?mnth=' . $month . $nat_qs . '">' . strftime( '%B', mktime( 0, 0, 0, $month, 13 ) ) . '</a>';
						if ( $i + 1 < count( $this->months ) ) {
							$selector .= ' | ';
						}
						$i++;
					}
					$output .= '</p></td></tr>';
				}

				if ( count( $this->nations ) > 1 ) {
					$selector .= '<tr><td><p class="label">' . __( 'Countries', 'vca-asm' ) . '</p>' .
						'<p class="metadata"><a ';
							if ( 0 == $nat_filter ) {
								$selector .= 'class="active-option" ';
							}
							$selector .= 'title="' . __( 'All countries&apos; activities', 'vca-asm' ) . '" href="' . get_bloginfo( 'url' ) . '?ctr=0' . $mnth_qs . '">' . __( 'All', 'vca-asm' ) . '</a> | ';
					$i = 0;
					foreach ( $this->nations as $nation ) {
						$selector .= '<a ';
						if ( $nation == $nat_filter ) {
							$selector .= 'class="active-option" ';
						}
						$selector .= 'title="' . __( 'Filter by country', 'vca-asm' ) . '" href="' . get_bloginfo( 'url' ) . '?ctr=' . $nation . $mnth_qs . '">' . $vca_asm_geography->get_name( $nation ) . '</a>';
						if ( $i + 1 < count( $this->nations ) ) {
							$selector .= ' | ';
						}
						$i++;
					}
					$output .= '</p></td></tr>';
				}

				$selector .= '</table></div>';

				$output = $selector . $output;
			}

		} else { // minimalistic === true

			$output = '';

			if ( ! empty( $pre_text ) ) {
				$output .= '<p class="message">' .
					$pre_text .
					'</p>';
			}

			$output .=  '<ul class="' . $list_class . '">';

			while ( $this->activities->have_posts() ) : $this->activities->the_post();

				$the_activity = new VCA_ASM_Activity( get_the_ID() );

				$output .= '<li class="activity">' .

						'<img class="activity-icon" alt="' . $the_activity->nice_type . '" src="' . $the_activity->icon_url . '" />' .

						'<h4><a title="' . __( 'Give me more information!', 'vca-asm' ) . '" href="' . get_permalink() . '">' . get_the_title() . '</a></h4>' .
						'<p class="type">' . $the_activity->nice_type . '</p>' .
						'<p class="no-margin">' .
							strftime( '%B %Y', intval( get_post_meta( get_the_ID(), 'start_act', true ) ) ) .
						'</p>' .

					'</li>';

			endwhile;

			$output .= '</ul>';

		}

		if ( true === $echo ) {
			echo $output;
		}
		return $output;
	}

} // class

endif; // class exists

?>