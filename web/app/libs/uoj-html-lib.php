<?php

function renderMarkdown($content_md) {
    $purifier = HTML::pruifier();

    try {
        $v8 = new V8Js();
        $v8->content_md = $content_md;
        $v8->executeString(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/js/marked.js'), 'marked.js');
        $content = $v8->executeString('marked(PHP.content_md)');
    } catch (V8JsException $e) {
        throw new Exception('V8Js error: ' . $e->getMessage());
    }

    $content = $purifier->purify($content);

    return $content;
}
