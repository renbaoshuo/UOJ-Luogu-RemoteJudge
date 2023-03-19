<?php

define('LUOGU_BASE_URL', 'https://www.luogu.com.cn');
define('LUOGU_API_BASEURL', 'https://open-v1.lgapi.cn');
define('LUOGU_SUPPORTED_LANGUAGES', ['C', 'C++', 'C++11', 'Java8', 'Pascal', 'Python2', 'Python3']);
define('LUOGU_USER_AGENT', 'UniversalOJ/1.0 UOJ-Luogu-RemoteJudge/1.0 ( https://github.com/renbaoshuo/UOJ-Luogu-RemoteJudge )');

function parseLuoguProblemData($problem) {
    if (!$problem) return null;

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
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    $curl->url(LUOGU_BASE_URL . '/problem/' . $pid . '?_contentOnly=1');

    if ($curl->error()) {
        throw new Exception('Curl error: ' . $curl->message());
    }

    $data = json_decode($curl->data(), true);

    return parseLuoguProblemData($data['currentData']['problem']);
}
