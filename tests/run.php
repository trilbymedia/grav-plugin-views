<?php

namespace Grav\Common\Config {
    class Config
    {
        private $items;

        public function __construct($items = [])
        {
            $this->items = is_array($items) ? $items : [];
        }

        public function get($key, $default = null)
        {
            $value = $this->items;
            foreach (explode('.', $key) as $part) {
                if (!is_array($value) || !array_key_exists($part, $value)) {
                    return $default;
                }
                $value = $value[$part];
            }

            return $value;
        }
    }
}

namespace Grav\Common\Filesystem {
    class Folder
    {
        public static function create($path)
        {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }
}

namespace Grav\Common {
    class Grav
    {
        private static $instance;

        public static function instance()
        {
            if (!self::$instance) {
                self::$instance = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
            }

            return self::$instance;
        }
    }

    class Plugin
    {
        public $grav;
        public $enabledEvents = [];
        private $admin = false;

        public function __construct()
        {
            $this->grav = Grav::instance();
        }

        public function setAdmin($admin)
        {
            $this->admin = $admin;
        }

        public function isAdmin()
        {
            return $this->admin;
        }

        public function enable(array $events)
        {
            $this->enabledEvents = array_merge($this->enabledEvents, $events);
        }
    }
}

namespace Grav\Plugin\Database {
    class PDO extends \PDO
    {
    }
}

namespace RocketTheme\Toolbox\Event {
    class Event extends \ArrayObject
    {
        public function __construct(array $items = [])
        {
            parent::__construct($items, \ArrayObject::ARRAY_AS_PROPS);
        }
    }
}

namespace Twig {
    class TwigFunction
    {
        public function __construct($name, $callable = null, array $options = [])
        {
        }
    }
}

namespace {
    require_once __DIR__ . '/../classes/Views.php';
    require_once __DIR__ . '/../views.php';

    use Grav\Common\Config\Config;
    use Grav\Common\Grav;
    use Grav\Plugin\Views\Views;
    use Grav\Plugin\ViewsPlugin;
    use RocketTheme\Toolbox\Event\Event;

    final class FakeLocator
    {
        private $path;

        public function __construct($path)
        {
            $this->path = $path;
        }

        public function findResource($path, $absolute = false, $create = false)
        {
            return $this->path;
        }
    }

    final class FakeDatabaseService
    {
        public $connectDsn;
        public $namedConnection;
        private $legacyPdo;
        private $namedPdo;

        public function __construct($legacyPdo, $namedPdo)
        {
            $this->legacyPdo = $legacyPdo;
            $this->namedPdo = $namedPdo;
        }

        public function connect($dsn)
        {
            $this->connectDsn = $dsn;
            return $this->legacyPdo;
        }

        public function __call($method, $arguments)
        {
            $this->namedConnection = [$method, $arguments[0] ?? null];
            return $this->namedPdo;
        }
    }

    final class FakePdo
    {
        public $prepared = [];
        public $queries = [];
        private $driver;
        private $hasTable;

        public function __construct($driver = 'sqlite', $hasTable = true)
        {
            $this->driver = $driver;
            $this->hasTable = $hasTable;
        }

        public function setAttribute($attribute, $value)
        {
        }

        public function getAttribute($attribute)
        {
            if ($attribute === \PDO::ATTR_DRIVER_NAME) {
                return $this->driver;
            }

            return null;
        }

        public function tableExists($table)
        {
            return $this->hasTable;
        }

        public function exec($query)
        {
            $this->queries[] = ['exec', $query];
        }

        public function prepare($query)
        {
            $this->prepared[] = $query;
            return new FakeStatement();
        }

        public function query($query)
        {
            $this->queries[] = ['query', $query];
            if (strpos($query, 'sqlite_version()') !== false && $this->driver !== 'sqlite') {
                throw new RuntimeException('PostgreSQL connections must not run SQLite version checks.');
            }

            return new FakeStatement(['3.44.0']);
        }
    }

    final class FakeStatement
    {
        private $row;

        public function __construct($row = [])
        {
            $this->row = $row;
        }

        public function bindValue($name, $value, $type = null)
        {
        }

        public function execute()
        {
        }

        public function fetch()
        {
            return $this->row;
        }

        public function fetchAll()
        {
            return [];
        }

        public function rowCount()
        {
            return 1;
        }
    }

    final class FakeViewsReporter
    {
        public function getAll($type = null, $limit = -1, $order = 'ASC')
        {
            return [
                ['id' => '/journal/example', 'count' => 7, 'type' => 'pages'],
            ];
        }
    }

    final class FakeRouteCollector
    {
        public $routes = [];

        public function get($route, $handler)
        {
            $this->routes[] = ['GET', $route, $handler];
            return $this;
        }
    }

    function assertSameValue($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            throw new RuntimeException(
                $message . PHP_EOL .
                'Expected: ' . var_export($expected, true) . PHP_EOL .
                'Actual: ' . var_export($actual, true)
            );
        }
    }

    function assertTrueValue($condition, $message)
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    function setGrav(FakeDatabaseService $database)
    {
        $grav = Grav::instance();
        $grav->exchangeArray([]);
        $grav['database'] = $database;
        $grav['locator'] = new FakeLocator(sys_get_temp_dir() . '/grav-views-tests');
        $grav['config'] = new Config([
            'plugins' => [
                'views' => [
                    'autotrack' => true,
                    'admin2' => [
                        'reports' => true,
                        'dashboard' => true,
                    ],
                ],
            ],
        ]);

        return $grav;
    }

    function testLegacySqliteConnectionRemainsDefault()
    {
        $legacyPdo = new FakePdo('sqlite');
        $database = new FakeDatabaseService($legacyPdo, new FakePdo('pgsql'));
        setGrav($database);

        new Views([]);

        assertTrueValue(strpos($database->connectDsn, 'sqlite:') === 0, 'Views should keep SQLite as the default connection.');
        assertSameValue(null, $database->namedConnection, 'Views should not use a named connection without database config.');
    }

    function testNamedDatabaseConnectionCanBeUsed()
    {
        $database = new FakeDatabaseService(new FakePdo('sqlite'), new FakePdo('pgsql'));
        setGrav($database);

        new Views([
            'database' => [
                'driver' => 'pgsql',
                'connection' => 'page_views',
            ],
        ]);

        assertSameValue(['pgsql', 'page_views'], $database->namedConnection, 'Views should use configured Database plugin named connections.');
        assertSameValue(null, $database->connectDsn, 'Views should not open the legacy SQLite file when a named connection is configured.');
    }

    function testPostgresTrackDoesNotRunSqliteVersionProbe()
    {
        $pdo = new FakePdo('pgsql');
        $database = new FakeDatabaseService(new FakePdo('sqlite'), $pdo);
        setGrav($database);

        $views = new Views([
            'database' => [
                'driver' => 'pgsql',
                'connection' => 'page_views',
            ],
        ]);
        $views->track('/journal/example');

        assertTrueValue(strpos($pdo->prepared[0], 'ON CONFLICT') !== false, 'PostgreSQL tracking should use an upsert query.');
    }

    function testUnlimitedGetAllOmitsLimitClause()
    {
        $pdo = new FakePdo('pgsql');
        $database = new FakeDatabaseService(new FakePdo('sqlite'), $pdo);
        setGrav($database);

        $views = new Views([
            'database' => [
                'driver' => 'pgsql',
                'connection' => 'page_views',
            ],
        ]);
        $views->getAll(null, -1, 'desc');

        assertTrueValue(strpos($pdo->prepared[0], 'LIMIT') === false, 'Unlimited getAll() should not bind a negative LIMIT.');
    }

    function testAdmin2ReportAndWidgetEventsAreRegistered()
    {
        assertTrueValue(method_exists(ViewsPlugin::class, 'onApiGenerateReports'), 'Views should expose an Admin2 report event handler.');
        assertTrueValue(method_exists(ViewsPlugin::class, 'onApiDashboardWidgets'), 'Views should expose an Admin2 dashboard widget event handler.');

        $grav = Grav::instance();
        $grav->exchangeArray([]);
        $grav['views'] = new FakeViewsReporter();

        $plugin = new ViewsPlugin();
        $reportEvent = new Event(['reports' => []]);
        $plugin->onApiGenerateReports($reportEvent);
        $reports = $reportEvent['reports'];

        assertSameValue('views', $reports[0]['id'] ?? null, 'Views should add an Admin2 report id.');
        assertSameValue('views', $reports[0]['provider'] ?? null, 'Views should use the plugin slug as the report provider.');
        assertSameValue('views-report', $reports[0]['component'] ?? null, 'Views should point Admin2 at the report component.');

        $widgetEvent = new Event(['widgets' => []]);
        $plugin->onApiDashboardWidgets($widgetEvent);
        $widgets = $widgetEvent['widgets'];

        assertSameValue('views.top-pages', $widgets[0]['id'] ?? null, 'Views should add a top pages dashboard widget.');
        assertSameValue('/gpm/plugins/views/widget-script', $widgets[0]['scriptUrl'] ?? null, 'Views widget should expose its Admin2 script URL.');
    }

    function testAdmin2ComponentFilesExist()
    {
        $report = __DIR__ . '/../admin-next/reports/views-report.js';
        $widget = __DIR__ . '/../admin-next/widgets/views.js';

        assertTrueValue(is_file($report), 'Views should ship an Admin2 report component.');
        assertTrueValue(is_file($widget), 'Views should ship an Admin2 widget component.');
        assertTrueValue(strpos(file_get_contents($report), 'window.__GRAV_REPORT_TAG') !== false, 'Report component should use the Admin2 report tag bridge.');
        assertTrueValue(strpos(file_get_contents($widget), 'window.__GRAV_WIDGET_TAG') !== false, 'Widget component should use the Admin2 widget tag bridge.');
    }

    function testDashboardWidgetEndpointIsRegistered()
    {
        assertTrueValue(method_exists(ViewsPlugin::class, 'onApiRegisterRoutes'), 'Views should register an API route for the dashboard widget.');

        $plugin = new ViewsPlugin();
        $collector = new FakeRouteCollector();
        $plugin->onApiRegisterRoutes(new Event(['routes' => $collector]));

        assertSameValue('/views/top-pages', $collector->routes[0][1] ?? null, 'Views should expose a lightweight top-pages route.');
        assertSameValue('topPages', $collector->routes[0][2][1] ?? null, 'Top-pages route should map to the controller action.');
    }

    function testWidgetUsesLightweightEndpointNotReports()
    {
        $widget = file_get_contents(__DIR__ . '/../admin-next/widgets/views.js');

        assertTrueValue(strpos($widget, '/views/top-pages') !== false, 'Widget should fetch the lightweight top-pages endpoint.');
        assertTrueValue(strpos($widget, '/reports') === false, 'Widget should not hit the heavyweight /reports endpoint.');
    }

    $tests = [
        'testLegacySqliteConnectionRemainsDefault',
        'testNamedDatabaseConnectionCanBeUsed',
        'testPostgresTrackDoesNotRunSqliteVersionProbe',
        'testUnlimitedGetAllOmitsLimitClause',
        'testAdmin2ReportAndWidgetEventsAreRegistered',
        'testAdmin2ComponentFilesExist',
        'testDashboardWidgetEndpointIsRegistered',
        'testWidgetUsesLightweightEndpointNotReports',
    ];

    foreach ($tests as $test) {
        try {
            $test();
        } catch (Throwable $error) {
            fwrite(STDERR, $error->getMessage() . PHP_EOL);
            exit(1);
        }
    }

    fwrite(STDOUT, "All views plugin tests passed\n");
}
