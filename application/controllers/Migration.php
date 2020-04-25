<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // can only be called from the command line
        if (!$this->input->is_cli_request()) {
            exit('Direct access is not allowed. This is a command line tool, use the terminal');
        }

        $this->load->dbforge();
    }


	/**
	 * create migraiton controller for cli executed
	 * @param $name
	 */
	public function create($name) {
        $this->make_migration_file($name);
    }


	/**
	 * method for execute migration
	 * @param null $version
	 */
	public function migrate($version = null) {
        $this->load->library('migration');

        if ($version != null) {
            if ($this->migration->version($version) === FALSE) {
                show_error($this->migration->error_string());
            } else {
                echo "Migrations run successfully" . PHP_EOL;
            }

            return;
        }

        if ($this->migration->latest() === FALSE) {
            show_error($this->migration->error_string());
        } else {
            echo "Migrations run successfully" . PHP_EOL;
        }
    }

	/**
	 * method for make migration file on application/migration
	 * @param $name
	 */
	protected function make_migration_file($name) {
        $date = new DateTime();
        $timestamp = $date->format('YmdHis');

        $table_name = strtolower($name);

        $path = APPPATH.'migrations/' . "$timestamp" . "_" . "$name.php";

        $my_migration = fopen($path, "w") or die("Unable to create migration file!");

        $migration_tempate = "<?php
class Migration_$name extends CI_Migration {
    public function up() {
    
    }
    
    public function down() {
        \$this->dbforge->drop_table('$table_name');
    }
}
        ";

        fwrite($my_migration, $migration_tempate);
        fclose($my_migration);

        echo "$path migration has successfully been created." . PHP_EOL;
    }

}