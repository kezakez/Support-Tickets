<div class="wrap">
<?php screen_icon( 'edit-pages' ); ?>
<h2><?php echo esc_html( __( 'Edit Ticket', 'suptic' ) ); ?></h2>

<?php echo suptic_admin_update_message(); ?>

<form action="<?php echo suptic_admin_url( 'edit_tickets.php' ); ?>" method="post">

<table class="widefat" style="margin-top: 0.5em;">
<tbody>
<tr>
<td style="padding: 0.5em 1em 1em;">

<div style="display: none">
<?php wp_nonce_field( 'suptic-edit-ticket-' . $ticket->id ); ?>
<input type="hidden" name="ticket-id" value="<?php echo $ticket->id; ?>" />
</div>

<div style="position: relative">

<h3><?php echo esc_html( $ticket->subject ); ?></h3>

<table class="ticket-description">
<tbody>
<tr valign="top">
<th scope="row"><?php echo esc_html( __( 'Author', 'suptic' ) ); ?></th>
<td><?php echo esc_html( $ticket->author_name() ); ?></td>
</tr>

<tr valign="top">
<th scope="row"><?php echo esc_html( __( 'Created', 'suptic' ) ); ?></th>
<td><?php echo suptic_human_time( $ticket->create_time ); ?></td>
</tr>

<tr valign="top">
<th scope="row"><?php echo esc_html( __( 'Updated', 'suptic' ) ); ?></th>
<td><?php echo suptic_human_time( $ticket->update_time ); ?></td>
</tr>

<tr valign="top">
<th scope="row"><?php echo esc_html( __( 'Status', 'suptic' ) ); ?></th>
<td><?php echo esc_html( $ticket->status ); ?></td>
</tr>

<?php if ( $metas = $ticket->get_metas() ) : ?>
<tr valign="top">
<th scope="row"><?php echo esc_html( __( 'Additional Fields', 'suptic' ) ); ?></th>
<td>
<table>
<tbody><?php foreach ( $metas as $meta ) : ?>
<tr>
<th><?php echo esc_html( $meta->meta_key ); ?></th>
<td><pre><?php echo esc_html( $meta->meta_value ); ?></pre></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</td>
</tr>
<?php endif; ?>

</tbody>
</table>

<div style="margin-top: 2em;">
<a href="<?php echo esc_attr( $ticket->url() ); ?>" class="button-primary" target="_blank"><?php echo esc_html( __( 'View Ticket Page', 'suptic' ) ); ?></a>

<?php if ( ! $ticket->has_status( 'closed' ) ) : ?>
<?php 	if ( ! $ticket->has_status( 'read' ) ) : ?>
&emsp;<input type="submit" name="suptic-mark-ticket" class="button" value="<?php echo esc_attr( __( 'Mark as Read', 'suptic' ) ); ?>" />
<?php 	endif ?>
&emsp;<input type="submit" name="suptic-close-ticket" class="button" value="<?php echo esc_attr( __( 'Close This Ticket', 'suptic' ) ); ?>" />
<?php else : ?>
&emsp;<input type="submit" name="suptic-reopen-ticket" class="button" value="<?php echo esc_attr( __( 'Reopen This Ticket', 'suptic' ) ); ?>" />
<?php endif; ?>

</div>

<div class="actions-link">
<?php $delete_nonce = wp_create_nonce( 'suptic-delete-ticket-' . $ticket->id ); ?>
<input type="submit" name="suptic-delete-ticket" class="delete" value="<?php echo esc_attr( __( 'Delete', 'suptic' ) ); ?>"
<?php echo "onclick=\"if (confirm('" .
esc_js( __( "You are about to delete this ticket.\n'Cancel' to stop, 'OK' to delete.", 'suptic' ) ) .
"')) {this.form._wpnonce.value = '$delete_nonce'; return true;} return false;\""; ?> />
</div>

</div>

</td>
</tr>
</tbody>
</table>

</form>

<?php do_action( 'suptic_edit_ticket', $ticket ); ?>

<?php if ( $messages = $ticket->messages() ) : ?>
<table class="widefat suptic-messages-under-ticket" style="margin-top: 20px;">
<thead><tr><th scope="col" colspan="3"><?php echo esc_html( __( 'Messages', 'suptic' ) ); ?></th></tr></thead>
<tbody>
<?php foreach ( (array) $messages as $message ) :

$num = $num + 1;

$class = '';
$alt = 1 - $alt;
if ( $alt ) $class .= ' alternate';
if ( ! $message->is_admin_reply() ) $class .= ' waiting-reply';
$class = trim( $class );

?>
<tr<?php echo empty( $class ) ? '' : ' class="' . $class . '"'; ?>>
<th class="num"><p><?php echo $num; ?></p></th>
<td class="avatar"><p><?php echo $message->avatar( 36 ); ?></p></td>
<td>
<p class="message-body"><?php echo nl2br( esc_html( $message->message_body ) ); ?></p>
<p class="desc">
	<?php echo esc_html( $message->author_name() ); ?>
	<?php echo $message->create_time; ?>
	<?php if ( $message->has_status( 'draft' ) )
		echo ' <span class="draft-notice">- ' . esc_html( __( 'Draft', 'suptic' ) ) . '</span>';
	?>
</p>

<p class="row-actions">
<a href="<?php echo wp_nonce_url( suptic_admin_url( 'edit-tickets.php', array( 'ticket_id' => $ticket->id, 'suptic-toggle-message-status' => 1, 'message_id' => $message->id ) ), 'suptic-toggle-message-status' ); ?>"><?php echo esc_html( $message->has_status( 'draft' ) ? __( 'Publish', 'suptic' ) : __( 'Draft', 'suptic' ) ); ?></a>
 | 
<a href="<?php echo suptic_admin_url( 'edit-tickets.php', array( 'message_id' => $message->id ) ); ?>"><?php echo esc_html( __( 'Edit', 'suptic' ) ); ?></a>
</p>

<?php do_action( 'suptic_message_under_ticket', $message, $ticket ); ?>

</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</div>