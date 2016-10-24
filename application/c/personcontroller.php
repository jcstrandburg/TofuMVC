<?php
/**
 * Description of usercontroller
 *
 * @author Justin Strandburg
 */
class PersonController extends BaseController {
	
	protected function makeRoutes() {
		return [		
			'index' => 'index',
			'edit' => 'edit',
			'insert' => 'insert',
			'update' => 'update',
			'delete' => 'delete',
		];
	}

	public function index() {
		$table = Database::table('person');
		$records = $table->select()->all()->fetchRecords();
		$viewData = [
			'persons'=>[],
			'possessions'=>[],
		];
		
		foreach ($records as $record) {			
			$userArray = $record->toArray();
			$id = $userArray['id'];
			$viewData['persons'][$id] = $userArray;
			$viewData['persons'][$id]['possessions'] = [];
			$possessions = $record->oneToMany('possession');
			foreach ($possessions as $p) {
				$viewData['persons'][$id]['possessions'][] = $p->toArray();
			}
		}
		
		return View::make('person/index')->withData(['data'=>$viewData]);
	}
	
	public function edit($id, $name=null) {
		$table = Database::table('person');
		$record = $table->load($id);
		$viewData = [
			'person' => null,
		];		
		
		if ($record === null) {
			Tofu::raiseError('Cannot find person '.$id);
		} else {
			$viewData['person'] = $record->toArray();
			$viewData['possessions'] = [];
			foreach ($record->oneToMany('possession') as $possession) {
				$viewData['possessions'][] = $possession->toArray();
			}
		}
		
		return View::make('person/edit')->withData($viewData);
	}
	
	public function insert() {
		$name = Request::input('person-name');
		$table = Database::table('person');		
		$newrecord = $table->newRecord();
		$newrecord['name'] = $name;
		if ($newrecord->save()) {
		} else {
			Tofu::raiseError('Failed to insert record!');
		}
		
		Tofu::redirect('person/index');		
		return View::make('default');
	}

	public function update($id) {
		$table = Database::table('person');
		$record = $table->load($id);
		$record['name'] = Request::input('person-name');
		if (!$record->save()) {
			Tofu::raiseError('Failed to save changes!');
			return View::make('default');			
		} else {
			//redirect here
			Tofu::redirect('person/index');
		}
	}
	
	public function delete($id) {
		$id = filter_var($id, FILTER_VALIDATE_INT);
		if ($id === null) {
			Tofu::raiseError('Invalid id');
			return View::make('default');
		}
		Database::table('possession')->delete()->where('person_id=?',[$id])->execute();
		Database::table('person')->delete()->where('id=?',[$id])->execute();
		Tofu::redirect('person/index');
	}
}
