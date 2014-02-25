<?php

require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/vca-asm/lib/class-php2excel.php' );
global $wpdb, $vca_asm_geography, $vca_asm_registrations, $vca_asm_utilities;

$id = $_GET['activity'];
$title = str_replace( ' ', '_', get_the_title( $id ) );
$end_act = get_post_meta( $id, 'end_act', true );
$year = date( 'Y', $end_act );
$filename = __( 'Participant_Data', 'vca-asm' ) . '_' . $title . '_' . $year . '.xls';

$xls = new ExportXLS( $filename );

$header = __( 'Participant Data', 'vca-asm' ) . ': ' . $title . ' (' . $year . ')';
$xls->addHeader($header);

$empty_row = null;
$xls->addHeader( $empty_row );
$xls->addHeader( $empty_row );

$header = array(
	__( 'Running Number', 'vca-asm' ),
	__( 'First Name', 'vca-asm' ),
	__( 'Last Name', 'vca-asm' ),
	__( 'City / Cell / Local Crew', 'vca-asm' ),
	__( 'Email-Address', 'vca-asm' )
);
$xls->addHeader( $header );

$xls->addHeader( $empty_row );

if ( is_numeric( $end_act ) && time() > $end_act ) {
	$registered_supporters = $vca_asm_registrations->get_activity_participants_old( $id );
} else {
	$registered_supporters = $vca_asm_registrations->get_activity_registrations( $id );
}

$rows = array();
$f_names = array();
$i = 0;
foreach( $registered_supporters as $supporter ) {
	$supp_info = get_userdata( $supporter );
	$supp_bday = get_user_meta( $supporter, 'birthday', true );
	if ( ! empty( $supp_bday ) && is_numeric( $supp_bday ) ) {
		$supp_age = $vca_asm_utilities->date_diff( time(), $supp_bday );
	} else {
		$supp_age = '???';
	}
	if ( is_numeric( $end_act ) && time() > $end_act ) {
		$notes = $wpdb->get_results(
			"SELECT notes FROM " .
			$wpdb->prefix . "vca_asm_registrations_old " .
			"WHERE activity=" . $id . " AND supporter=" . $supporter . ' LIMIT 1', ARRAY_A
		);
	} else {
		$notes = $wpdb->get_results(
			"SELECT notes FROM " .
			$wpdb->prefix . "vca_asm_registrations " .
			"WHERE activity=" . $id . " AND supporter=" . $supporter . ' LIMIT 1', ARRAY_A
		);
	}
	$note = str_replace( '"', '&quot;', str_replace( "'", '&apos;', $notes[0]['notes'] ) );
	if ( is_object( $supp_info ) ) {
		$rows[$i] = array(
			$supp_info->first_name,
			$supp_info->last_name,
			$vca_asm_geography->get_name( get_user_meta( $supporter, 'region', true ) ),
			$supp_info->user_email
		);
	} else {
		$rows[$i] = array(
			__( 'Not a member of the Pool anymore...', 'vca-asm' )
		);
	}
	if ( is_object( $supp_info ) ) {
		$f_names[$i] = $supp_info->first_name;
	} else {
		$f_names[$i] = 'ZZZZZZ';
	}
	$i++;
}

array_multisort( $f_names, $rows );

$i = 1;
foreach( $rows as $row ) {
	array_unshift( $row, $i );
	$i++;
	$xls->addRow( $row );
}

$xls->sendFile();

?>