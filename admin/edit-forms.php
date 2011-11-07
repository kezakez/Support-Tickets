<?php

$form_id = $_GET['form_id'];
$forms = (array) suptic_get_forms();

if ( 'new' == $form_id ) {
	$initial = true;
} elseif ( ( $form_id = (int) $form_id ) ) {
	$form = suptic_get_form( $form_id );
} else {
	$form = $forms[0];
	if ( $form )
		$form_id = $form->id;
	else
		$initial = true;
}

if ( ! $form )
	$form = suptic_default_form();

$form_name = $form->name;
$form_design = $form->form_design;
$form_page = (int) $form->page_id;

?><div class="wrap">
<?php screen_icon( 'edit-pages' ); ?>
<h2><?php echo esc_html( __( 'Edit Forms', 'suptic' ) ); ?></h2>

<?php echo suptic_admin_update_message(); ?>

<ul class="subsubsub">
<?php foreach ( $forms as $form ) : ?>
<li><a href="<?php echo suptic_admin_url( 'edit-forms.php', array( 'form_id' => $form->id ) ); ?>"<?php if ( $form->id == $form_id ) echo ' class="current"'; ?>><?php echo esc_html( $form->name ); ?></a> |</li>
<?php endforeach; ?>

<li class="addnew"><a href="<?php echo suptic_admin_url( 'edit-forms.php', array( 'form_id' => 'new' ) ); ?>"<?php if ( $initial ) echo ' class="current"'; ?>><?php echo esc_html( __( 'Add new', 'suptic' ) ); ?></a></li>
</ul>

<br class="clear" />

<form action="<?php echo suptic_admin_url( 'edit_forms.php' ); ?>" method="post">
<table class="widefat">
<tbody>
<tr>
<td scope="col">
<div style="position: relative;">

<div style="display: none">
<?php
if ( $initial )
	wp_nonce_field( 'suptic-create-form' );
else
	wp_nonce_field( 'suptic-edit-form-' . $form_id );
?>
<input type="hidden" name="form-id" value="<?php echo $form_id; ?>" />
</div>

<p><input type="text" name="form-name" id="form-name" size="60" value="<?php echo esc_attr( $form_name ); ?>" style="width: 80%" /></p>

<p><label for="form-page"><strong><?php echo esc_html( __( 'Form Page', 'suptic' ) ); ?></strong></label>&nbsp;
<?php wp_dropdown_pages( array(
	'selected' => $form_page, 'name' => 'form-page',
	'show_option_none' => __( '- Select -', 'suptic' ) ) );

if ( $form_page ) {
	echo ' <a href="' . esc_attr( get_page_link( $form_page ) )
		. '" class="button" target="_blank">'
		. esc_html( __( 'View Page', 'suptic' ) ) . '</a>';
}
?>
</p>

<div class="save-form">
<?php if ( $initial ) : ?>
<input type="submit" name="suptic-create-form" class="button-primary" value="<?php echo esc_attr( __( 'Create Form', 'suptic' ) ); ?>" />
<?php else : ?>
<input type="submit" name="suptic-edit-form" class="button-primary" value="<?php echo esc_attr( __( 'Update Form', 'suptic' ) ); ?>" />
<?php endif; ?>
</div>

<?php if ( ! $initial ) : ?>
<div class="actions-link">
<?php $delete_nonce = wp_create_nonce( 'suptic-delete-form-' . $form_id ); ?>
<input type="submit" name="suptic-delete-form" class="delete" value="<?php echo esc_attr( __( 'Delete Form', 'suptic' ) ); ?>"
<?php echo "onclick=\"if (confirm('" .
esc_js( __( "You are about to delete this form.\n'Cancel' to stop, 'OK' to delete.", 'suptic' ) ) .
"')) {this.form._wpnonce.value = '$delete_nonce'; return true;} return false;\""; ?> />
</div>
<?php endif; ?>

</div>
</td>
</tr>
</tbody>
</table>

<table class="widefat" style="margin-top: 1em;">
<thead><tr><th scope="col" colspan="2"><?php echo esc_html( __( 'Form Design', 'suptic' ) ); ?></th></tr></thead>
<tbody>
<tr>
<td scope="col" style="width: 50%;">
<div><textarea name="form-design" id="form-design" cols="100" rows="20" style="width: 100%;"><?php echo esc_html( $form_design ); ?></textarea></div>
</td>

<td scope="col" style="width: 50%;">
<div id="tag-generator-div"></div>
</td>

</tr>
</tbody>
</table>

<table class="widefat" style="margin: 1em 0;">
<tbody>
<tr>
<td scope="col">
<div class="save-form">
<?php if ( $initial ) : ?>
<input type="submit" name="suptic-create-form" class="button-primary" value="<?php echo esc_attr( __( 'Create Form', 'suptic' ) ); ?>" />
<?php else : ?>
<input type="submit" name="suptic-edit-form" class="button-primary" value="<?php echo esc_attr( __( 'Update Form', 'suptic' ) ); ?>" />
<?php endif; ?>
</div>
</td>
</tr>
</tbody>
</table>

</form>

</div>