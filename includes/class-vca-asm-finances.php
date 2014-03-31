<?php

/**
 * VCA_ASM_Finances class.
 *
 * This class contains properties and methods for
 * the handling of financial data
 *
 * @package VcA Activity & Supporter Management
 * @since 1.5
 */

if ( ! class_exists( 'VCA_ASM_Finances' ) ) :

class VCA_ASM_Finances
{

	/**
	 * Class Properties
	 *
	 * @since 1.5
	 */
	public $donations_transactions = array( 'donation', 'transfer' );
	public $econ_transactions = array( 'revenue', 'expenditure', 'transfer' );

	public $types_to_nicenames = array(
		'donation' => 'Donation',
		'expenditure' => 'Expenditure',
		'revenue' => 'Revenue',
		'transfer' => 'Transfer',
		'income' => 'Income Account',
		'expense' => 'Expense Account'
	);

	/**
	 * Constructor
	 *
	 * @since 1.5
	 * @access public
	 */
	public function __construct( $args = array() )
	{
		$this->types_to_nicenames = array(
			'donation' => __( 'Donation', 'vca-asm' ),
			'expenditure' => __( 'Expenditure', 'vca-asm' ),
			'revenue' => __( 'Revenue', 'vca-asm' ),
			'transfer' => __( 'Transfer', 'vca-asm' ),
			'income' => __( 'Income Account', 'vca-asm' ),
			'expense' => __( 'Expense Account', 'vca-asm' )
		);
	}

	/**
	 * Returns the specified type of transactions of a city
	 *
	 * @param array $args
	 * @return array $transactions
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_annual( $args = array() )
	{

	}

	/**
	 * Returns the specified type of transactions of a city
	 *
	 * @param array $args
	 * @return array $transactions
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_transactions( $args = array() )
	{
		global $wpdb;

		$default_args = array(
			'city_id' => 0,
			'account_type' => 'donations',
			'transaction_type' => 'donation',
			'annual' => false,
			'date_limit' => false,
			'orderby' => 'transaction_date',
			'order' => 'DESC',
			'receipt_status' => false,
			'sum' => false
		);
		$args = wp_parse_args( $args, $default_args );
		extract( $args );

		$where = "WHERE city_id = " . $city_id . " AND account_type = '" . $account_type . "'";
		if ( 'donations' === $account_type )
		{
			if ( ! in_array( $transaction_type, $this->donations_transactions ) ) {
				$where .= " AND transaction_type IN ('" . implode( "','", $this->donations_transactions ) . "')";
			} else {
				$where .= " AND transaction_type = '" . $transaction_type . "'";
			}
		} else {
			if ( ! in_array( $transaction_type, $this->econ_transactions ) ) {
				$where .= " AND transaction_type IN ('" . implode( "','", $this->econ_transactions ) . "')";
			} else {
				$where .= " AND transaction_type = '" . $transaction_type . "'";
			}
		}
		if ( is_numeric( $annual ) ) {
			$where .= " AND transaction_date > " . mktime( 0, 0, 1, 1, 1, $annual ) . " AND transaction_date < " . mktime( 23, 59, 59, 12, 31, $annual );
		} elseif ( is_numeric( $date_limit ) ) {
			$where .= " AND transaction_date > " . $date_limit;
		}
		if ( is_numeric( $receipt_status ) && in_array( $receipt_status, array( 0, 1, 2, 3 ) ) ) {
			$where .= " AND receipt_status = " . $receipt_status;
		}

		$transactions = $wpdb->get_results(
			"SELECT * FROM " .
			$wpdb->prefix . "vca_asm_finances_transactions " .
			$where . " " .
			"ORDER BY " . $orderby . " " . $order,
			ARRAY_A
		);

		$increment = 0;
		if ( true === $sum ) {
			if ( ! empty( $transactions ) ) {
				foreach ( $transactions as $transaction ) {
					$increment += abs( intval( $transaction['amount'] ) );
				}
			}
			$transactions = $increment;
		}

		return $transactions;
	}

	/**
	 * Returns the type of a transaction
	 *
	 * @param int $id
	 * @return string $type
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_transaction_type( $id, $with_acc_type = false )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT account_type, transaction_type FROM " .
			$wpdb->prefix . "vca_asm_finances_transactions " .
			"WHERE id = ".$id, ARRAY_A
		);

		$return = $with_acc_type && isset( $data[0]['account_type'] ) ? $data[0]['account_type'] : '';
		$return .= isset( $data[0]['transaction_type'] ) && ! empty( $return ) ? '-' : '';
		$return .= isset( $data[0]['transaction_type'] ) ? $data[0]['transaction_type'] : '';

		return $return;
	}

	/**
	 * Returns the city belonging to a transaction
	 *
	 * @param int $id
	 * @return int $city_id
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_transaction_city( $id )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT city_id FROM " .
			$wpdb->prefix . "vca_asm_finances_transactions " .
			"WHERE id = ".$id, ARRAY_A
		);

		$return = isset( $data[0]['city_id'] ) ? $data[0]['city_id'] : 0;

		return $return;
	}


	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_account( $city_id, $type = 'econ' )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT * FROM " .
			$wpdb->prefix . "vca_asm_finances_accounts " .
			"WHERE city_id = " . $city_id . " AND type = '" . $type . "' " .
			"LIMIT 1", ARRAY_A
		);

		$value = isset( $data[0] ) ? $data[0] : false;

		return $value;
	}


	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_accounts( $type = 'econ', $nation_id = 0, $with_extra_data = false, $sorted = false )
	{
		global $wpdb,
			$vca_asm_geography;

		$where = "WHERE type = '" . $type . "'";
		if ( ! empty( $nation_id ) ) {
			$where .= " AND city_id IN (" .
				$vca_asm_geography->get_descendants(
					$nation_id,
					array(
						'data' => 'id',
						'format' => 'string',
						'concat' => ',',
						'type' => 'city'
					)
				) .
				")";
		}
		$data = $wpdb->get_results(
			"SELECT * FROM " .
			$wpdb->prefix . "vca_asm_finances_accounts " .
			$where,
			ARRAY_A
		);

		if ( $with_extra_data ) {
			$i = 0;
			foreach ( $data as $account ) {
				$data[$i] = $account;
				$data[$i]['name'] = $vca_asm_geography->get_name( $account['city_id'] );
				$data[$i]['balance_raw'] = intval( $this->get_balance( $account['city_id'], $type ) );
				$data[$i]['balance'] = number_format( $data[$i]['balance_raw']/100, 2, ',', '.' );
				$i++;
			}
			if ( $sorted ) {
				global $vca_asm_utilities;
				$data = $vca_asm_utilities->sort_by_key( $data, 'name' );
			}
		}

		return $data;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function create_account( $city_id, $type = 'econ' )
	{
		global $wpdb;

		$data = $wpdb->insert(
			$wpdb->prefix . "vca_asm_finances_accounts",
			array(
				'city_id' => $city_id,
				'type' => $type,
				'balance' => 0,
				'last_updated' => time(),
				'balanced_month' => strftime( '%Y-%m', strtotime( date(' Y/m/d' ) . '-1 month' ) )
			),
			array( '%d', '%s', '%d', '%d', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_balanced_month( $city_id, $type = 'econ' )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT balanced_month FROM " .
			$wpdb->prefix . "vca_asm_finances_accounts " .
			"WHERE city_id = " . $city_id . " AND type = '" . $type . "' " .
			"LIMIT 1", ARRAY_A
		);

		$value = isset( $data[0]['balanced_month'] ) ? $data[0]['balanced_month'] : strftime( '%Y-%m', strtotime( strftime( '%Y-%m', time() ) . ' -6 month' ) ); // ! isset condition for testing only

		return $value;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_balance( $city_id, $type = 'econ' )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT balance, last_updated FROM " .
			$wpdb->prefix . "vca_asm_finances_accounts " .
			"WHERE city_id = " . $city_id . " AND type = '" . $type . "' " .
			"LIMIT 1", ARRAY_A
		);

		$balance = isset( $data[0]['balance'] ) ? $data[0]['balance'] : 0;

		$where = "WHERE city_id = " . $city_id . " AND account_type = '" . $type . "' AND entry_time > ";
		$where .= isset( $data[0]['last_updated'] ) ? $data[0]['last_updated'] : 0;

		$data = $wpdb->get_results(
			"SELECT * FROM " .
			$wpdb->prefix . "vca_asm_finances_transactions " .
			$where, ARRAY_A
		);

		if ( ! empty( $data ) )
		{
			foreach( $data as $transaction )
			{
				if (
					(
						'donations' === $type &&
						(
							'transfer' === $transaction['transaction_type'] ||
							( 'donation' === $transaction['transaction_type'] && 1 == $transaction['cash'] )
						)
					) || (
						'econ' === $type
					)
				) {
					$balance += $transaction['amount'];
				}
			}

			$wpdb->update(
				$wpdb->prefix . "vca_asm_finances_accounts",
				array( 'last_updated' => time(), 'balance' => $balance ),
				array( 'city_id' => $city_id, 'type' => $type ),
				array( '%d', '%d' ),
				array( '%d', '%s' )
			);
		}

		return $balance;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_donations( $city_id, $with_years = false )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT amount, transaction_date FROM " .
			$wpdb->prefix . "vca_asm_finances_transactions " .
			"WHERE city_id = " . $city_id . " AND account_type = 'donations' AND transaction_type = 'donation' " .
			"ORDER BY transaction_date DESC",
			ARRAY_A
		);

		$years = array( 'total' => 0 );

		if ( ! empty( $data ) ) {
			foreach( $data as $transaction ) {
				if ( $with_years ) {
					$year = strftime( '%Y', $transaction['transaction_date'] );
					if ( ! array_key_exists( $year, $years ) ) {
						$years[$year] = $transaction['amount'];
					} else {
						$years[$year] += $transaction['amount'];
					}
				}
				$years['total'] += $transaction['amount'];
			}
		}

		return $with_years ? $years : $years['total'];
	}

	/***** META *****/

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_meta( $id = 0, $id_type = 'id', $type = '', $select = 'all' )
	{
		global $wpdb;

		$select = 'all' === $select ? '*' : $select;

		$where_type = ! empty( $type ) ? " AND type = '" . $type . "' " : " ";

		$data = $wpdb->get_results(
			"SELECT " . $select . " FROM " .
			$wpdb->prefix . "vca_asm_finances_meta " .
			"WHERE " . $id_type . " = " . $id . $where_type .
			"LIMIT 1", ARRAY_A
		);

		if ( isset( $data[0] ) ) {
			$value = '*' === $select ? $data[0] : ( isset( $data[0][$select] ) ? $data[0][$select] : false );
		} else {
			$value = false;
		}

		return $value;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_metas( $orderby = 'value', $order = 'ASC', $type = 'cost-center', $nation = 0 )
	{
		global $wpdb;

		$where = "WHERE type = '" . $type . "' ";
		if ( ! empty( $nation ) && is_numeric( $nation ) ) {
			$where .= "AND related_id = " . $nation . " ";
		}

		$data = $wpdb->get_results(
			"SELECT * FROM " .
			$wpdb->prefix . "vca_asm_finances_meta " .
			$where .
			"ORDER BY " .
			$orderby . " " . $order, ARRAY_A
		);

		return $data;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_cash_account( $city_id = 0 )
	{
		return $this->get_meta( $city_id, 'related_id', 'cash-acc', 'value' );
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_cost_center( $id = 0, $data_type = 'all' )
	{
		return $this->get_meta( $id, 'id', 'cost-center', $data_type );
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_cost_centers( $orderby = 'value', $order = 'ASC', $nation = 0 )
	{
		return $this->get_metas( $orderby, $order, 'cost-center', $nation );
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_ei_account( $id = 0 )
	{
		return $this->get_meta( $id, 'id' );
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_ei_accounts( $orderby = 'value', $order = 'ASC', $type = 'income', $nation = 0 )
	{
		return $this->get_metas( $orderby, $order, $type, $nation );
	}

	/**
	 * Returns an array of region data
	 * to be used in either a dropdown menu or a checkbox group
	 *
	 * @since 1.0 (modified for 1.3)
	 * @access public
	 */
	public function ei_options_array( $args ) {
		global $vca_asm_utilities;

		$default_args = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'please_select' => false,
			'please_select_value' => 'please_select',
			'please_select_text' => __( 'Please select...', 'vca-asm' ),
			'unclear' => false,
			'unclear_value' => 0,
			'unclear_text' => __( 'I don&apos;t know...', 'vca-asm' ),
			'type' => 'income',
			'nation' => 0
		);
		extract( wp_parse_args( $args, $default_args ), EXTR_SKIP );

		$data = $this->get_ei_accounts( $orderby, $order, $type, $nation );

		$options_array = array();

		if( true === $please_select ) {
			$options_array[0] = array(
				'label' => $please_select_text,
				'value' => $please_select_value,
				'class' => 'please-select'
			);
		}

		foreach( $data as $account ) {
			$options_array[] = array(
				'label' => $account['description'],
				'value' => $account['id'],
				'class' => $account['type']
			);
		}

		if( true === $unclear ) {
			$options_array[] = array(
				'label' => $unclear_text,
				'value' => $unclear_value,
				'class' => 'dunno'
			);
		}

		return $options_array;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_related_id( $id )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT related_id FROM " .
			$wpdb->prefix . "vca_asm_finances_meta " .
			"WHERE id = " . $id . " " .
			"LIMIT 1", ARRAY_A
		);

		$value = isset( $data[0]['related_id'] ) ? $data[0]['related_id'] : '';

		return $value;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_limit( $nation, $type )
	{
		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT value FROM " .
			$wpdb->prefix . "vca_asm_finances_meta " .
			"WHERE related_id = " . $nation . " AND type = 'limit-" . $type . "' " .
			"LIMIT 1",
			ARRAY_A
		);

		$value = isset( $data[0]['value'] ) ? $data[0]['value'] : false;

		return $value;
	}

	/**
	 * ???
	 *
	 * @since 1.5
	 * @access public
	 */
	public function generate_receipt( $city_id, $type = 'econ' )
	{
		global $wpdb,
			$vca_asm_geography;

		$data = $wpdb->get_results(
			"SELECT last_receipt " .
			"FROM " . $wpdb->prefix . "vca_asm_finances_accounts " .
			"WHERE city_id = " . $city_id . " AND type = '" . $type . "' " .
			"LIMIT 1",
			ARRAY_A
		);

		$alpha_code = $vca_asm_geography->get_alpha_code( $city_id );

		$current_year = strftime( '%Y' );
		$current_month = strftime( '%m' );
		$next_running = 1;
		if ( isset( $data[0]['last_receipt'] ) ) {
			list( $bullshit, $year, $month, $running_number ) = explode( '-', $data[0]['last_receipt'] );
			if ( $current_year === $year && $current_month === $month ) {
				$next_running = intval( $running_number ) + 1;
			}
		}

		$next_receipt = $alpha_code . '-' . $current_year . '-' . $current_month . '-' . str_pad( $next_running, 3, '0', STR_PAD_LEFT );

		return $next_receipt;
	}

	/**
	 * ???
	 * 0 - no receipt
	 * 1 - receipt required, not yet sent
	 * 2 - receipt required, sent, not yet received
	 * 3 - receipt received
	 *
	 * @since 1.5
	 * @access public
	 */
	public function get_receipts( $city_id, $args )
	{
		$default_args = array(
			'status' => 1,
			'type' => 'econ',
			'split' => false,
			'data_type' => 'all'
		);
		$args = wp_parse_args( $args, $default_args );
		extract( $args );

		global $wpdb;

		$data = $wpdb->get_results(
			"SELECT * " .
			"FROM " . $wpdb->prefix . "vca_asm_finances_transactions " .
			"WHERE city_id = " . $city_id . " AND account_type = '" . $type . "' AND receipt_status = " . $status,
			ARRAY_A
		);

		$return = array();
		if ( $split ) {
			$return = array( 'late' => array(), 'current' => array() );
			if ( ! empty( $data ) ) {
				foreach ( $data as $transaction ) {
					if ( $transaction['receipt_date'] >= date( 'm-01-Y 00:00:00' ) ) {
						$return['current'][] = ! empty( $transaction[$data_type] ) ? $transaction[$data_type] : $transaction;
					} else {
						$return['late'][] = ! empty( $transaction[$data_type] ) ? $transaction[$data_type] : $transaction;
					}
				}
			}
		} elseif( 'all' !== $data_type && ! empty( $data[0][$data_type] ) ) {
			foreach ( $data as $transaction ) {
				$return[] = ! empty( $transaction[$data_type] ) ? $transaction[$data_type] : $transaction;
			}
		} else {
			$return = $data;
		}

		return $return;
	}

} // class

endif; // class exists

?>