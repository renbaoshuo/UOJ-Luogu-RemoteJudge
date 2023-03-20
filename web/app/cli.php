<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require $_SERVER['DOCUMENT_ROOT'] . '/app/libs/uoj-lib.php';

requirePHPLib('luogu');
requirePHPLib('data');

// TODO: more beautiful argv parser
$my_args = array();

for ($i = 1; $i < count($argv); $i++) {
    if (preg_match('/^--([^=]+)[=](.*)/', $argv[$i], $match)) {
        $my_args[$match[1]] = $match[2];
    } else if (preg_match('/^--(.*)/', $argv[$i], $match)) {
        $my_args[$match[1]] = $argv[++$i];
    }
}

$handlers = [
	'upgrade:up' => function ($name) {
		if (func_num_args() != 1) {
			die("php cli.php upgrade:up <name>\n");
		}
		Upgrader::transaction(function() use ($name) {
			Upgrader::up($name);
		});
		die("finished!\n");
	},
	'upgrade:down' => function ($name) {
		if (func_num_args() != 1) {
			die("php cli.php upgrade:down <name>\n");
		}
		Upgrader::transaction(function() use ($name) {
			Upgrader::down($name);
		});
		die("finished!\n");
	},
	'upgrade:refresh' => function ($name) {
		if (func_num_args() != 1) {
			die("php cli.php upgrade:refresh <name>\n");
		}
		Upgrader::transaction(function() use ($name) {
			Upgrader::refresh($name);
		});
		die("finished!\n");
	},
	'upgrade:remove' => function ($name) {
		if (func_num_args() != 1) {
			die("php cli.php upgrade:remove <name>\n");
		}
		Upgrader::transaction(function() use ($name) {
			Upgrader::remove($name);
		});
		die("finished!\n");
	},
	'upgrade:latest' => function () {
		if (func_num_args() != 0) {
			die("php cli.php upgrade:latest\n");
		}
		Upgrader::transaction(function() {
			Upgrader::upgradeToLatest();
		});
		die("finished!\n");
	},
	'upgrade:remove-all' => function () {
		if (func_num_args() != 0) {
			die("php cli.php upgrade:remove-all\n");
		}
		Upgrader::transaction(function() {
			Upgrader::removeAll();
		});
		die("finished!\n");
	},
	'luogu:add-problem' => function () use ($argv, $my_args) {
		DB::init();

		$db = array();

		if (!isset($my_args['file'])) {
			echo "No database file specified, fetching online instead.\n\n";
		} else {
			echo "Reading local database: {$my_args['file']}\n\n";

			$file = file_get_contents($my_args['file']);

			foreach (explode("\n", $file) as $line) {
				if (strlen($line) == 0) continue;

				$line_data = json_decode($line, true);
				$db[$line_data['pid']] = $line_data;
			}

			$db_count = count($db);
			echo "Loaded {$db_count} items.\n\n";
		}

		// TODO: read database from local file

		$problems = array_filter($argv, function ($id) {
			if (!validateLuoguProblemId($id)) return false;

			return true;
		});

		if (empty($problems)) {
			echo "No problems to be added.\n";

			return;
		}

		echo "Problems to be added: " . implode(', ', $problems) . "\n\n";

		readline("Press Enter to continue, or Ctrl+C to abort.");

		foreach ($problems as $pid) {
			try {
				if (!isset($db[$pid])) {
					if (!empty($db)) {
						echo "[WARN] $pid: fallback to fetch data online\n";
					}

					$id = newLuoguRemoteProblem($pid);
				} else {
					$parsed = parseLuoguProblemData($db[$pid]);
					$id = newLuoguRemoteProblemFromData($parsed);
				}

				echo "$pid: $id\n";
			} catch (Exception $e) {
				echo "$pid: failed\n";
			}
		}
	},
	'help' => 'showHelp',
];

function showHelp() {
	global $handlers;
	echo "UOJ Command-Line Interface\n";
	echo "php cli.php <task-name> params1 params2 ...\n";
	echo "\n";
	echo "The following tasks are available:\n";
	foreach ($handlers as $cmd => $handler) {
		echo "\t$cmd\n";
	}
}

if (count($argv) <= 1) {
	showHelp();
	die();
}

if (!isset($handlers[$argv[1]])) {
	echo "Invalid parameters.\n";
	showHelp();
	die();
}

call_user_func_array($handlers[$argv[1]], array_slice($argv, 2));
