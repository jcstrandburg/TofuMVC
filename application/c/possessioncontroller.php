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
				$newrecord['description'] = Request::input('possession-desc');
				$newrecord->save();
			} catch (Exception $e) {
				Tofu::raiseError($e->getMessage());
			}
			return View::make('default');
		}
	}

	public function update() {
	}
	
	public function delete() {
	}	
}
