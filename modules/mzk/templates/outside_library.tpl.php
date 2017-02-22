<?php
$this->includeAtTemplateBase('includes/header.php');
?>
<hr/>
<hr/>
<h2><?php echo $this->t('{mzk:login:walkin_outside_library}'); ?></h2>
<hr/>
<hr/>
<?php
echo "<br/>";
echo $this->t('{mzk:login:walkin_logged_out}'); 
echo "<br/>";
echo "<br/>";
echo $this->t('{mzk:login:walkin_outside_please_return}'); 
echo "<br/>";
echo "<br/>";
echo $this->t('{mzk:login:may_close_window}'); 
$this->includeAtTemplateBase('includes/footer.php');
