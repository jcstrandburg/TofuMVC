<?php
class YController extends BaseController {
	/**
	 * Gets all mapped routes for the controller
	 * @return array[string=>string] An array index on action names with values being method names
	 */	
	protected function makeRoutes() {
		return [		
			'index' => 'index',
			'xyz' => 'xyz',
		];
	}

	public function index() {
		set_time_limit(10);
		
		for ($x = 0; $x < 10; $x++) {
			sleep(2);
		}
		
		//sleep(9);
		
		return "junk";
		
		$ch = curl_init();
		curl_setopt_array($ch,[
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => 'http://192.241.232.84/',
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		if ($result === false) {
			$errno = curl_errno($ch);
			$message = curl_strerror($errno);
			echo "Error: ".$message;
		} else {
			echo $result;
		}
		return $result;
		
		return view::make();
	}
    
	public function xyz($field1, $field2) {
		$table = Database::table('table1');
		$r = $table->newRecord();
		$r->id = 6;
		$r->delete();
		$all = $r->toArray();

		/*$all = $table->allRecords();
		$table->where('bitchboy=2')->delete()->execute();
		$table->update('junk=?',[1])->where('satan is not null')->execute();
		$table->delete()->where('x=5')->execute();*/

		Tofu::raiseWarning('Dis is a warning');
		
		$viewData = [
			'request'=>Request::allInputs(),
			//'primaryKey'=>$schema->primaryKey, 
			//'columns'=>$schema->columns,
			'all' => $all,
			'field1'=>$field1,
			'field2'=>$field2,
			'showForm'=>Request::getMethod() !== 'POST',	
		];
		return View::make('default')->withData($viewData);
	}

	public function kyoto(Request $request) {
		echo 'KYOTO!';
	}
}