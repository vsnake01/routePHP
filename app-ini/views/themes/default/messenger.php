<?php
if (\Messenger::get()) {
?>
<div class="alert <?php echo \Messenger::getType()?>">
  <?php echo \Messenger::get()?>
</div>
<?php 
	\Messenger::delete();
}?>
