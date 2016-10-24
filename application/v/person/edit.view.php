<?php $this->includeFragment('header'); ?>

<div class='container'>
	<h3>Edit Person</h3>
</div>

<div class="container">
	<?= $this->formOpen('post','person/update/'.$person['id']); ?>
	<label>Name: </lable>
	<input type='text' name='person-name' value='<?= $person['name'] ?>'></input>
	<input type='submit' name='submit' value='Submit Name Change' class='btn btn-primary'>
	<?= $this->formClose(); ?>
</div>

<div class='container'>
	<?php foreach($possessions as $possession): ?>
		Possession: <?= $possession['name']; ?> (<?= $possession['quantity']?>)
		<a href='<?=$this->routeURL('possession/edit/'.$possession['id']);?>' class='btn btn-primary btn-xs'>Edit</a>
		<a href='<?=$this->routeURL('possession/delete/'.$possession['id']);?>' class='btn btn-danger btn-xs'>Trash</a>
		<br>
	<?php endforeach; ?>
</div>

<hr>

<div class='container'>
	<?= $this->formOpen('post', "possession/insert/{$person['id']}"); ?>
		<label for='possession-name'>Name:</label><input type='text' name='possession-name' id='possession-name'><br>
		<label for='possession-qty'>Quantity:</label><input type='text' name='possession-qty' id='possession-qty'><br>	
		<label for='possession-desc'>Description:</label><input type='text' name='possession-desc' id='possession-desc'><br>
		<input type='submit' name='submit' value='New Possession' class='btn btn-primary'>
	<?= $this->formClose(); ?>
</div>

<hr>

<div class='container'>
	<a href='<?=$this->routeURL('person/delete/'.$person['id'])?>' class='btn btn-danger' role='button'>Trash Person</a>
</div>

<?php $this->includeFragment('footer'); ?>
