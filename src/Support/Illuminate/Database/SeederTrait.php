<?php

namespace Support\Illuminate\Database;

use DB;
use Schema;
use Config;

/**
* seeder helper class for for cleaning the table
*/
trait SeederTrait
{
    /**
     * enable and disable foreign key constraints
     * @param  boolean $enable [description]
     * @return string          db statement
     */
    protected function getForeignKeyCheck($enable = true)
    {
        $default = Config::get('database.default');
        switch ($default) {
            case 'sqlite':
                $string = 'PRAGMA foreign_keys = ' . ($enable ? 'ON' : 'OFF');
                break;
            default:
                $string = 'SET FOREIGN_KEY_CHECKS = ' . ($enable ? '1' : '0');
                break;
         }
        return $string;
    }

    /**
     * cleaning process of tables
     * @return [type] [description]
     */
    protected function cleanDatabase(array $tables)
    {
        DB::statement($this->getForeignKeyCheck(false));
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement($this->getForeignKeyCheck(true));
    }
}