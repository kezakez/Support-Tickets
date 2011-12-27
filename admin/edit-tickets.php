<?php

/* One ticket view */

if ( ( $ticket_id = (int) $_GET['ticket_id'] )
	&& ( $ticket = suptic_get_ticket( $ticket_id ) ) ) {

	require_once SUPTIC_PLUGIN_DIR . '/admin/edit-ticket.php';
	return;
} elseif ( ( $message_id = (int) $_GET['message_id'] )
	&& ( $message = suptic_get_message( $message_id ) ) ) {

	require_once SUPTIC_PLUGIN_DIR . '/admin/edit-message.php';
	return;
}

/* Ticket list view */

$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 0;

if ( empty( $pagenum ) )
	$pagenum = 1;

$ticketsperpage = 10;
$pageoffset = ( $pagenum - 1 ) * $ticketsperpage;

$view_by_status = $_GET['status'];
if ( 'closed' == $view_by_status ) {
	$status = array( 'closed' );
} elseif ( 'replied' == $view_by_status ) {
	$status = array( 'admin_replied' );
} elseif ( 'all' == $view_by_status ) {
	$status = null;
} else {
	$view_by_status = 'pending'; // default
	$status = array( 'new', 'waiting_reply' );
}

$orderby = $_GET['orderby'];
if ( 'ticket_id' != $orderby && 'create_time' != $orderby )
	$orderby = 'update_time'; // default

$search = stripslashes( $_GET['s'] );

$args = array(
	'perpage' => $ticketsperpage,
	'offset' => $pageoffset,
	'orderby' => $orderby,
	'status' => $status,
	'search' => $search );

$tickets = apply_filters( 'suptic_admin_tickets_on_edit_tickets',
	suptic_get_tickets( $args ), $args );

?><div class="wrap">
<?php screen_icon( 'edit-pages' ); ?>
<h2><?php echo esc_html( __( 'Edit Tickets', 'suptic' ) ); ?></h2>

<?php echo suptic_admin_update_message(); ?>

<?php
$subsubsub = array();
$subsubsub[] = '<a href="' . suptic_admin_url( 'edit-tickets.php', array( 'status' => 'all' ) )
	. '"' . ( 'all' == $view_by_status ? ' class="current"' : '' ) . '>'
	. esc_html( __( 'All', 'suptic' ) )
	. ' <span class="count">(' . suptic_count_tickets() . ')</span></a>';
$subsubsub[] = '<a href="' . suptic_admin_url( 'edit-tickets.php', array( 'status' => 'pending' ) )
	. '"' . ( 'pending' == $view_by_status ? ' class="current"' : '' ) . '>'
	. esc_html( __( 'Pending', 'suptic' ) )
	. ' <span class="count">('
	. suptic_count_tickets( array( 'status' => array( 'new', 'waiting_reply' ) ) )
	. ')</span></a>';
$subsubsub[] = '<a href="' . suptic_admin_url( 'edit-tickets.php', array( 'status' => 'replied' ) )
	. '"' . ( 'replied' == $view_by_status ? ' class="current"' : '' ) . '>'
	. esc_html( __( 'Replied', 'suptic' ) )
	. ' <span class="count">('
	. suptic_count_tickets( array( 'status' => array( 'admin_replied' ) ) )
	. ')</span></a>';
$subsubsub[] = '<a href="' . suptic_admin_url( 'edit-tickets.php', array( 'status' => 'closed' ) )
	. '"' . ( 'closed' == $view_by_status ? ' class="current"' : '' ) . '>'
	. esc_html( __( 'Closed', 'suptic' ) )
	. ' <span class="count">('
	. suptic_count_tickets( array( 'status' => array( 'closed' ) ) )
	. ')</span></a>';

$subsubsub = apply_filters( 'suptic_edit_tickets_subsubsub', $subsubsub );
echo suptic_generate_subsubsub( $subsubsub );
?>

<form class="search-form" action="" method="get">
<p class="search-box">
<label class="screen-reader-text" for="ticket-search-input"><?php echo esc_html( __( 'Search Tickets', 'suptic' ) ); ?>:</label>
<input type="hidden" name="page" value="<?php echo SUPTIC_PLUGIN_NAME . '/admin/edit-tickets.php'; ?>" />
<input type="text" id="ticket-search-input" name="s" value="<?php _admin_search_query(); ?>" />
<input type="submit" value="<?php echo esc_attr( __( 'Search Tickets', 'suptic' ) ); ?>" class="button" />
</p>
</form>
<br class="clear" />

<form id="tickets-filter" action="" method="get">
<?php do_action( 'suptic_admin_tickets_filter' ); ?>
<div class="tablenav">

<div class="alignleft actions">

<select name="action">
<option value="-1" selected="selected"><?php echo esc_html( __( 'Bulk Actions', 'suptic' ) ); ?></option>
<option value="close"><?php echo esc_html( __( 'Close', 'suptic' ) ); ?></option>
<option value="delete"><?php echo esc_html( __( 'Delete', 'suptic' ) ); ?></option>
</select>
<input type="submit" value="<?php echo esc_attr( __( 'Apply', 'suptic' ) ); ?>" name="suptic-bulk-edit-tickets" id="suptic-bulk-edit-tickets" class="button-secondary action" />
<?php wp_nonce_field( 'suptic-bulk-edit-tickets' ); ?>

<select name="orderby">
<option value="update_time"<?php if ( 'update_time' == $orderby )
	echo ' selected="selected"'; ?>><?php echo esc_html( __( 'Order by Update Time', 'suptic' ) ); ?></option>
<option value="create_time"<?php if ( 'create_time' == $orderby )
	echo ' selected="selected"'; ?>><?php echo esc_html( __( 'Order by Create Time', 'suptic' ) ); ?></option>
<option value="ticket_id"<?php if ( 'ticket_id' == $orderby )
	echo ' selected="selected"'; ?>><?php echo esc_html( __( 'Order by Ticket ID', 'suptic' ) ); ?></option>
</select>

<input type="submit" id="post-query-submit" value="<?php
	echo esc_attr( __( 'Filter', 'suptic' ) ); ?>" class="button-secondary" />
<input type="hidden" name="page" value="<?php echo SUPTIC_PLUGIN_NAME . '/admin/edit-tickets.php'; ?>" />
<input type="hidden" name="status" value="<?php echo esc_attr( $view_by_status ); ?>" />
</div>

<br class="clear" />
</div>

<div class="clear"></div>

<table class="widefat">
<thead>
<tr>
<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Subject', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Ticket', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Author', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Messages', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Form', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Created', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Updated', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Status', 'suptic' ) ); ?></th>
</tr>
</thead>

<tfoot>
<tr>
<th scope="col"  class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Subject', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Ticket', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Author', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Messages', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Form', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Created', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Updated', 'suptic' ) ); ?></th>
<th scope="col" class="manage-column column-name" style=""><?php echo esc_html( __( 'Status', 'suptic' ) ); ?></th>
</tr>
</tfoot>

<tbody>
<?php foreach ( (array) $tickets as $ticket ) :
$class = '';
$alt = 1 - $alt;
if ( $alt ) $class .= ' alternate';

if ( $ticket->has_status( 'new' ) )
	$class .= ' ticket-status-new';
if ( $ticket->has_status( 'waiting_reply' ) )
	$class .= ' ticket-status-waiting-reply';
if ( $ticket->has_status( 'closed' ) )
	$class .= ' ticket-status-closed';

$class = trim( $class );
?>
<tr<?php echo empty( $class ) ? '' : ' class="' . $class . '"'; ?>>
<th scope="row" class="check-column"><input type="checkbox" name="ticket[]" value="<?php echo esc_attr( $ticket->id ); ?>" /></th>
<td><a href="<?php echo esc_attr( $ticket->url() ); ?>"><strong><?php echo esc_html( $ticket->subject ); ?></strong></a>

<p class="row-actions"><a href="<?php echo suptic_admin_url( 'edit-tickets.php', array( 'ticket_id' => $ticket->id ) ); ?>"><?php echo esc_html( __( 'Edit', 'suptic' ) ); ?></a></p>
</td>
<td><?php echo esc_html( $ticket->id ); ?></td>
<td><?php echo esc_html( $ticket->author_name() ); ?></td>
<td><?php echo esc_html( $ticket->message_count() ); ?></td>
<td><?php $form = $ticket->form();
echo '<a href="' . suptic_admin_url( 'edit-forms.php', array( 'form_id' => $form->id ) ) . '">'
	. esc_html( $form->name ) . '</a>'; ?></td>
<td><?php echo suptic_human_time( $ticket->create_time ); ?></td>
<td><?php echo suptic_human_time( $ticket->update_time ); ?></td>
<td><?php echo esc_html( $ticket->status ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="tablenav">
<div class="alignleft actions">
<select name="action2">
<option value="-1" selected="selected"><?php echo esc_html( __( 'Bulk Actions', 'suptic' ) ); ?></option>
<option value="close"><?php echo esc_html( __( 'Close', 'suptic' ) ); ?></option>
<option value="delete"><?php echo esc_html( __( 'Delete', 'suptic' ) ); ?></option>
</select>
<input type="submit" value="<?php echo esc_attr( __( 'Apply', 'suptic' ) ); ?>" name="suptic-bulk-edit-tickets2" id="suptic-bulk-edit-tickets2" class="button-secondary action" />
<br class="clear" />
</div>
<br class="clear" />
</div>

<?php
$args = array( 'status' => $status, 'search' => $search );
$total_t = apply_filters( 'suptic_admin_tickets_on_edit_tickets',
	suptic_get_tickets( $args ), $args );
$total = ceil( count( $total_t ) / $ticketsperpage );
$page_links = paginate_links( array(
	'base' => add_query_arg( 'pagenum', '%#%' ),
	'format' => '',
	'prev_text' => __( '&laquo;', 'aag' ),
	'next_text' => __( '&raquo;', 'aag' ),
	'total' => $total,
	'current' => $pagenum
	) );

if ( $page_links )
	echo '<div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div>';
?>

</form>

</div>