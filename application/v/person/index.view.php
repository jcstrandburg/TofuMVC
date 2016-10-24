<?php $this->includeFragment('header'); ?>

<div class="container">
	<?php foreach ($data['persons'] as $person): ?>
	<a href='<?= $this->routeURL('person/edit/'.$person['id'])?>'><?= $person['name'] ?></a>
	 - (<?php echo count($person['possessions']); ?> Possessions)	
	<br>
	<?php endforeach; ?>
</div>

<hr>

<div class='container'>
	<?= $this->formOpen('post', 'person/insert'); ?>
	<label for='person-name'>Name: </label>
	<input type='text' name='person-name' id='person-name'>
	<input type='submit' name='submit' value='New Person' class='btn btn-primary'>
	<?= $this->formClose(); ?>
</div>

<?php $this->includeFragment('footer'); ?>
