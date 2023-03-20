<?php

// requires uoj-data-lib

define('LUOGU_BASE_URL', 'https://www.luogu.com.cn');
define('LUOGU_API_BASEURL', 'https://open-v1.lgapi.cn');
define('LUOGU_SUPPORTED_LANGUAGES', array('C', 'C++', 'C++11', 'Java8', 'Pascal', 'Python2', 'Python3'));
define('LUOGU_USER_AGENT', 'UniversalOJ/1.0 UOJ-Luogu-RemoteJudge/1.0 ( https://github.com/renbaoshuo/UOJ-Luogu-RemoteJudge )');

function parseLuoguProblemData($problem) {
    if (!$problem) throw new Exception('Problem not found');

    $statement = '';

    if ($problem['background']) {
        $statement .= "\n### 题目背景\n\n";
        $statement .= $problem['background'] . "\n";
    }

    $statement .= "\n### 题目描述\n\n";
    $statement .= $problem['description'] . "\n";

    if (isset($problem['translation']) && $problem['translation']) {
        $statement .= "\n### 题意翻译\n\n";
        $statement .= $problem['translation'] . "\n";
    }

    $statement .= "\n### 输入格式\n\n";
    $statement .= $problem['inputFormat'] . "\n";

    $statement .= "\n### 输出格式\n\n";
    $statement .= $problem['outputFormat'] . "\n";

    $statement .= "\n### 输入输出样例\n\n";

    foreach ($problem['samples'] as $id => $sample) {
        $display_sample_id = $id + 1;

        $statement .= "\n#### 样例输入 #{$display_sample_id}\n\n";
        $statement .= "\n```text\n{$sample[0]}\n```\n\n";

        $statement .= "\n#### 样例输出 #{$display_sample_id}\n\n";
        $statement .= "\n```text\n{$sample[1]}\n```\n\n";
    }

    $statement .= "\n### 说明/提示\n\n";
    $statement .= $problem['hint'] . "\n";

    return [
        'pid' => $problem['pid'],
        'title' => "【洛谷 {$problem['pid']}】{$problem['title']}",
        'time_limit' => (float)max($problem['limits']['time']) / 1000.0,
        'memory_limit' => (float)max($problem['limits']['memory']) / 1024.0,
        'statement' => renderMarkdown($statement),
        'statement_md' => $statement,
    ];
}

function fetchLuoguProblemBasicInfo($pid) {
    // ensure validateLuoguProblemId($pid) is true

    $curl = Curl::init();

    $curl->set('CURLOPT_HTTPHEADER', [
        'User-Agent: ' . LUOGU_USER_AGENT,
        'Accept: application/json',
    ]);

    $curl->url(LUOGU_BASE_URL . '/problem/' . $pid . '?_contentOnly=1');

    if ($curl->error()) {
        throw new Exception('Curl error: ' . $curl->message());
    }

    $data = json_decode($curl->data(), true);

    return parseLuoguProblemData($data['currentData']['problem']);
}

function newLuoguRemoteProblem($pid) {
    // ensure validateLuoguProblemId($pid) is true

    $problem = fetchLuoguProblemBasicInfo($pid);

    $esc_submission_requirements = json_encode(array(
        array(
            "name" => "answer",
            "type" => "source code",
            "file_name" => "answer.code",
            "languages" => LUOGU_SUPPORTED_LANGUAGES,
        ),
    ));
    $esc_extra_config = json_encode(array(
        "luogu_pid" => $problem['pid'],
        "time_limit" => $problem['time_limit'],
        "memory_limit" => $problem['memory_limit'],
        "view_content_type" => "ALL",
        "view_details_type" => "ALL",
    ));

    DB::insert("insert into problems (title, is_hidden, submission_requirement, extra_config, hackable, type) values ('" . DB::escape($problem['title']) . "', 1, '" . DB::escape($esc_submission_requirements) . "', '" . DB::escape($esc_extra_config) . "', 0, 'luogu')");

    $id = DB::insert_id();

    DB::insert("insert into problems_contents (id, statement, statement_md) values ($id, '" . DB::escape($problem['statement']) . "', '" . DB::escape($problem['statement_md']) . "')");

    dataNewProblem($id);

    return $id;
}

function refetchLuoguProblemInfo($id) {
    $problem = queryProblemBrief($id);
    $problem_extra_config = getProblemExtraConfig($problem);
    $pid = $problem_extra_config['luogu_pid'];

    $luogu_problem = fetchLuoguProblemBasicInfo($pid);

    $esc_submission_requirements = json_encode(array(
        array(
            "name" => "answer",
            "type" => "source code",
            "file_name" => "answer.code",
            "languages" => LUOGU_SUPPORTED_LANGUAGES,
        ),
    ));
    mergeConfig($problem_extra_config, array(
        "time_limit" => $luogu_problem['time_limit'],
        "memory_limit" => $luogu_problem['memory_limit'],
    ));
    $esc_extra_config = json_encode($problem_extra_config);

    DB::update("update problems set title = '" . DB::escape($luogu_problem['title']) . "', submission_requirement = '" . DB::escape($esc_submission_requirements) . "', extra_config = '" . DB::escape($esc_extra_config) . "' where id = {$problem['id']}");
    DB::update("update problems_contents set statement = '" . DB::escape($luogu_problem['statement']) . "', statement_md = '" . DB::escape($luogu_problem['statement_md']) . "' where id = {$problem['id']}");
}
