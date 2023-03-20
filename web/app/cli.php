<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require $_SERVER['DOCUMENT_ROOT'] . '/app/libs/uoj-lib.php';

requirePHPLib('luogu');
requirePHPLib('data');

// TODO: more beautiful argv parser

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
	'luogu:add-problem' => function () use ($argv) {
		$rest_index = null;
		$opts = getopt('', ['file::'], $rest_index);

		if (!isset($opts['file'])) {
			echo "No database file specified, fetching online instead.\n\n";
		}

		// TODO: read database from local file

		$problems = array_slice($argv, $rest_index);
		$problems = array_filter($problems, function ($id) {
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
				$id = newLuoguRemoteProblem($pid);

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
