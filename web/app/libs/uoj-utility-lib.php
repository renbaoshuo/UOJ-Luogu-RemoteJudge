<?php

function getProblemCustomTestRequirement($problem) {
	$extra_config = json_decode($problem['extra_config'], true);
	if ($problem['type'] != 'local') {
		return array();
	} elseif (isset($extra_config['custom_test_requirement'])) {
		return $extra_config['custom_test_requirement'];
	} else {
		$answer = array(
			'name' => 'answer',
			'type' => 'source code',
			'file_name' => 'answer.code'
		);
		foreach (getProblemSubmissionRequirement($problem) as $req) {
			if ($req['name'] == 'answer' && $req['type'] == 'source code' && isset($req['languages'])) {
				$answer['languages'] = $req['languages'];
			}
		}
		return array(
			$answer,
			array(
				'name' => 'input',
				'type' => 'text',
				'file_name' => 'input.txt'
			)
		);
	}
}
