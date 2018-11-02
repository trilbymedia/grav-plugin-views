<?php
namespace Grav\Plugin\Views;

use Grav\Common\Grav;
use Grav\Common\Config\Config;
use Grav\Plugin\Database\PDO;

class Views
{
    /** @var PDO */
    protected $db;

    protected $config;
    protected $path = '/views';
    protected $db_name = '/views.db';
    protected $table_total_views = 'total_views';

    public function __construct($config)
    {
        $this->config = new Config($config);
        $db_path = Grav::instance()['locator']->findResource('user://data', true) . $this->path;

        // Create dir if it doesn't exist
        if (!file_exists($db_path)) {
            mkdir($db_path);
        }

        $connect_string = 'sqlite:' . $db_path . $this->db_name;

        $this->db = Grav::instance()['database']->connect($connect_string);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!$this->db->tableExists($this->table_total_views)) {
            $this->createTables();
        }

    }

    public function track($id, $amount = 1)
    {
        $statement = "INSERT INTO {$this->table_total_views} (id, count) VALUES ('$id', $amount) ON CONFLICT(id) DO UPDATE SET count = count + $amount";
        $this->db->insert($statement);
    }

    public function set($id, $amount = 0)
    {
        $statement = "INSERT INTO {$this->table_total_views} (id, count) VALUES ('$id', $amount) ON CONFLICT(id) DO UPDATE SET count = $amount";
        $this->db->insert($statement);
    }

    public function get($id)
    {
        $statement = "SELECT count FROM {$this->table_total_views} WHERE id = '$id'";
        $results = $this->db->select($statement);

        return $results['count'] ?? 0;
    }

    public function getAll($limit = 0, $order = 'ASC')
    {
        $limit = $limit ? ' LIMIT ' . $limit : '';
        $order = $order ? ' ORDER BY count ' . strtoupper($order) : '';
        $statement = "SELECT id, count FROM {$this->table_total_views}" . $order . $limit;

        $results = $this->db->selectall($statement);

        return $results;
    }

    public function createTables()
    {
        $commands = [
            "CREATE TABLE IF NOT EXISTS total_views (id VARCHAR(255) PRIMARY KEY, count INTEGER DEFAULT 0)",
        ];

        // execute the sql commands to create new tables
        foreach ($commands as $command) {
            $this->db->exec($command);
        }
    }



}
