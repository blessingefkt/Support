<?php namespace Iyoworks\Support\Traits;

trait MigrationTrait {

	public function uid($table, $len = null){
		return $table->string('uid', $len ?: 36)->unique();
	}

	public function metaCols($table, $len = null)
	{
		$table->timestamps();
		$this->uid($table, $len);
	}

	public function foreignId($table, $col, $otherTable = null, $col2 = 'id'){
		$table->unsignedInteger($col)->nullable();
		
		if($otherTable)
		 	$table->foreign($col)->references($col2)->on($otherTable);
	}

	public function foreignString($table, $col, $otherTable = null, $col2 = null){
		$table->string($col);
		
		if($otherTable)
		 	$table->foreign($col)->references($col2)->on($otherTable);
	}

	public function morphs($table, $name)
	{
		$table->integer("{$name}_id");
		$table->string("{$name}_type");
	}
}
