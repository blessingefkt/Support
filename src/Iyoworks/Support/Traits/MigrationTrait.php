<?php namespace Iyoworks\Support\Traits;

use \Illuminate\Support\Fluent;

trait MigrationTrait {

    /**
     * @param $table
     * @param null $len
     * @return Fluent
     */
    public function uid($table, $len = null){
        return $table->string('uid', $len ?: 100)->unique();
    }

    public function metaCols($table, $len = null)
    {
        $table->timestamps();
        $this->uid($table, $len);
    }

    /**
     * @param $table
     * @param $col
     * @param null $otherTable
     * @param string $col2
     * @return Fluent
     */
    public function foreignId($table, $col, $otherTable = null, $col2 = 'id'){
        $fluent = $table->unsignedInteger($col)->nullable();

        if($otherTable)
            $fluent = $table->foreign($col)->references($col2)->on($otherTable);
        return $fluent;
    }

    /**
     * @param $table
     * @param $col
     * @param null $otherTable
     * @param string $col2
     * @return Fluent
     */
    public function foreignString($table, $col, $otherTable = null, $col2 = null){
        $fluent = $table->string($col);

        if($otherTable)
            $fluent = $table->foreign($col)->references($col2)->on($otherTable);
        return $fluent;
    }

    public function morphs($table, $name)
    {
        $table->integer("{$name}_id");
        $table->string("{$name}_type");
    }
}
