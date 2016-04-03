<?php $this->includeFragment('header'); ?>

<div class="container">
	<?php foreach ($data['persons'] as $person): ?>
		<a href='<?php $this->routeURL('person/edit/'.$person['id'])?>'>Blondie</a>
	<?php endforeach; ?>
	<?php //echo pprint($data); ?>
</div>

<?= $this->formOpen('post', 'person/insert'); ?>
<h3>Create New Person</h3>
<label for='person-name'>Name: </label>
<input type='text' name='person-name' id='person-name'>
<br>
<input type='submit' name='submit' value='Create Person'>
<?= $this->formClose(); ?>

<?php $this->includeFragment('footer'); ?>
