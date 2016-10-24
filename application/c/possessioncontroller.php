<?php
class PossessionController extends BaseController {
	
	protected function makeRoutes() {
		return [
			'edit' => 'edit',
			'insert' => 'insert',
			'update' => 'update',
			'delete' => 'delete',
		];
	}
	
	public function edit($id, $dummyname=null) {
		$possessionTable = Database::table('possession');
		$possession = $possessionTable->load($id);
		$viewData = [
			'possession'=>$possession->toObject(),
			'owner'=>$possession->manyToOne('person')->name,
		];
		return View::make('possession/edit')->withData($viewData);
	}
    
	public function insert($personid) {		
		$personid = filter_var($personid, FILTER_VALIDATE_INT);
		if ($personid === false) {
			Tofu::raiseError('Invalid person id '.$personid);
		} else {
			try {
				$table = Database::table('possession');
				$newrecord = $table->newRecord();
				$newrecord['person_id'] = $personid;
				$newrecord['name'] = Request::input('possession-name');
				$newrecord['quantity'] = Request::input('possession-qty');
				$newrecord['description'] = Request::input('possession-desc');
				if ($newrecord->save()) {
					Tofu::redirect('person/edit/'.$personid);				
				} else {
					Tofu::raiseError('Failed to save!');
					return View::make('default');
				}
			} catch (Exception $e) {
				Tofu::raiseError($e->getMessage());
				return View::make('default');
			}
		}
	}

	public function update($id) {
		$id = filter_var($id, FILTER_VALIDATE_INT);
		if ($id === false) {
			Tofu::raiseError('Invalid id');
			return View::make('default');
		}
		
		$possession = Database::table('possession')->load($id);
		$possession['name'] = Request::input('possession-name');
		$possession['quantity'] = Request::input('possession-qty');
		$possession['description'] = Request::input('possession-desc');
		if ($possession->save()) {
			$owner = $possession->manyToOne('person');
			Tofu::redirect('person/edit/'.$owner->id);
		} else {
			Tofu::raiseError('Failed to save changes!');
			return View::make('default');
		}
	}
	
	public function delete($id) {
		$id = filter_var($id, FILTER_VALIDATE_INT);
		if ($id === false) {
			Tofu::raiseError('Invalid id');
			return View::make('default');
		}

		$possession = Database::table('possession')->load($id);
		$owner = $possession->manyToOne('person');
		
		if (!$possession->delete()) {
			Tofu::raiseError('Failed to delete possession!');
			return View::make('default');
		}
		
		if ($owner !== null) {
			Tofu::redirect('person/edit/'.$owner->id);
		} else {
			Tofu::redirect('person/index');
		}		
	}	
}
