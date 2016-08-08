<?php

$this->data['header'] = 'OpenID Login';
$this->data['autofocus'] = 'openid-identifier';
$this->includeAtTemplateBase('includes/header.php');

?>

<?php if ($this->data['error'] !== NULL) { ?>
	<div id="error">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" style="float: right; margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p style="clear: both"><b><?php echo $this->t('{errors:title_' . $this->data['error'] . '}'); ?></b></p>
	</div>
<?php } ?>


<?php
$this->includeAtTemplateBase('includes/footer.php');
?>