<?php $this->includeFragment('header'); ?>

<div class="container">
	<?= $this->formOpen('post','person/update'); ?>
	<label>Name</lable>
	<input type='text' value='<?= $data['person']['name'] ?>'></input>
	<br>
	<input type='submit' name='submit' value='Submit Name Change'>
	<?= $this->formClose(); ?>
</div>

<div class='container'>
	<?= $this->formOpen('post', "possession/insert/{$data['person']['id']}"); ?>
	<label for='possession-name'>Name:</label><input type='text' name='possession-name' id='possession-name'><br>
	<label for='possession-qty'>Quantity:</label><input type='text' name='possession-qty' id='possession-qty'><br>	
	<label for='possession-desc'>Description:</label><input type='text' name='possession-desc' id='possession-desc'><br>
	<input type='submit' name='submit' value='New Possession'>
	<?= $this->formClose(); ?>
</div>

<?php $this->includeFragment('footer'); ?>
