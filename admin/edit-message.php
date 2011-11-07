<div class="wrap">
<?php screen_icon( 'edit-pages' ); ?>
<h2><?php echo esc_html( __( 'Edit Message', 'suptic' ) ); ?></h2>

<?php echo suptic_admin_update_message(); ?>

<form action="<?php echo suptic_admin_url( 'edit_tickets.php' ); ?>" method="post">
<table class="widefat" style="margin-top: 0.5em;">
<tbody>
<tr>
<td style="padding: 0.5em 1em 1em;">

<div style="display: none">
<?php wp_nonce_field( 'suptic-edit-message-' . $message->id ); ?>
<input type="hidden" name="message-id" value="<?php echo $message->id; ?>" />
</div>

<div style="position: relative">

<textarea name="message-body" style="width: 90%;" cols="60" rows="20"><?php echo esc_html( $message->message_body ); ?></textarea>

<div style="position: absolute; right: 0; bottom: 0; text-align: right;">
<input type="submit" name="suptic-edit-message" class="button-primary" value="<?php echo esc_attr( __( 'Save', 'suptic' ) ); ?>" />
</div>

<div class="actions-link">
<?php $delete_nonce = wp_create_nonce( 'suptic-delete-message-' . $message->id ); ?>
<input type="submit" name="suptic-delete-message" class="delete" value="<?php echo esc_attr( __( 'Delete', 'suptic' ) ); ?>"
<?php echo "onclick=\"if (confirm('" .
esc_js( __( "You are about to delete this message.\n'Cancel' to stop, 'OK' to delete.", 'suptic' ) ) .
"')) {this.form._wpnonce.value = '$delete_nonce'; return true;} return false;\""; ?> />
</div>

</div>

</td>
</tr>
</tbody>
</table>
</form>

</div>