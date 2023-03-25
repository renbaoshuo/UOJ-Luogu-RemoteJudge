import superagent from 'superagent';

import Logger from './utils/logger.js';
import sleep from './utils/sleep.js';
import htmlspecialchars from './utils/htmlspecialchars.js';

const logger = new Logger('remote/luogu');

const USER_AGENT = 'UniversalOJ/1.0 UOJ-Luogu-RemoteJudge/1.0 ( https://github.com/renbaoshuo/UOJ-Luogu-RemoteJudge )';
const HTTP_ERROR_MAP = {
  400: 'Bad Request',
  401: 'Unauthorized',
  402: 'Payment Required',
  403: 'Forbidden',
  404: 'Not Found',
  405: 'Method Not Allowed',
  500: 'Internal Server Error',
  502: 'Bad Gateway',
  503: 'Service Unavailable',
  504: 'Gateway Timeout',
};
const STATUS_MAP = [
  'Waiting',
  'Judging',
  'Compile Error',
  'Output Limit Exceeded',
  'Memory Limit Exceeded',
  'Time Limit Exceeded',
  'Wrong Answer',
  'Runtime Error',
  0,
  0,
  0,
  'Judgement Failed',
  'Accepted',
  0,
  'Wrong Answer', // WA
];
const LANGS_MAP = {
  C: {
    id: 'c/99/gcc',
    name: 'C',
    comment: '//',
  },
  'C++': {
    id: 'cxx/98/gcc',
    name: 'C++ 98',
    comment: '//',
  },
  'C++11': {
    id: 'cxx/11/gcc',
    name: 'C++ 11',
    comment: '//',
  },
  Python3: {
    id: 'python3/c',
    name: 'Python 3',
    comment: '#',
  },
  Java8: {
    id: 'java/8',
    name: 'Java 8',
    comment: '//',
  },
  Pascal: {
    id: 'pascal/fpc',
    name: 'Pascal',
    comment: '//',
  },
};

function buildLuoguTestCaseInfoBlock(test) {
  const attrs = [
    ['num', test.id],
    ['info', STATUS_MAP[test.status]],
    ['time', test.time || -1],
    ['memory', test.memory || -1],
    ['score', test.score || ''],
  ]
    .map(o => `${o[0]}="${o[1]}"`)
    .join(' ');
  const desc = htmlspecialchars(test.description || '');

  return `<test ${attrs}><res>${desc}</res></test>`;
}

export default class Luogu {
  account;

  constructor(account) {
    if (!account) {
      throw new Error('No account info provided');
    }

    this.account = account;
  }

  get(url) {
    if (!url.includes('//')) {
      url = `${this.account.endpoint || 'https://open-v1.lgapi.cn'}${url}`;
    }

    logger.debug('get', url, this.cookie);

    const req = superagent
      .get(url)
      .set('User-Agent', USER_AGENT)
      .auth(this.account.username, this.account.password)
      .set('X-Requested-With', 'S2OJ Remote Judge (OpenSource Version)');

    return req;
  }

  post(url) {
    if (!url.includes('//')) {
      url = `${this.account.endpoint || 'https://open-v1.lgapi.cn'}${url}`;
    }

    logger.debug('post', url);

    const req = superagent
      .post(url)
      .set('User-Agent', USER_AGENT)
      .auth(this.account.username, this.account.password)
      .set('X-Requested-With', 'S2OJ Remote Judge (OpenSource Version)');

    return req;
  }

  async submitProblem(id, lang, code, submissionId, next, end) {
    if (code.length < 10) {
      await end({
        error: true,
        status: 'Compile Error',
        message: 'Code too short',
      });

      return null;
    }

    const programType = LANGS_MAP[lang] || LANGS_MAP['C++'];
    const comment = programType.comment;

    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array) code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }

    const result = await this.post('/judge/problem')
      .send({
        pid: id,
        code,
        lang: programType.id,
        o2: 1,
        trackId: submissionId,
      })
      .ok(status => true);

    if (HTTP_ERROR_MAP[result.status]) {
      await end({
        error: true,
        status: 'Judgement Failed',
        message: `[Luogu] ${HTTP_ERROR_MAP[result.status]}.`,
      });

      logger.error('submitProblem', result.status, HTTP_ERROR_MAP[result.status]);

      return null;
    }

    return result.body.requestId;
  }

  async waitForSubmission(id, next, end) {
    let fail = 0;
    let count = 0;

    while (count < 360 && fail < 60) {
      await sleep(500);

      count++;

      try {
        const result = await this.get(`/judge/result?id=${id}`).ok((status) => true);

        if (HTTP_ERROR_MAP[result.status]) {
          await end({
            error: true,
            status: 'Judgement Failed',
            message: `[Luogu] ${HTTP_ERROR_MAP[result.status]}.`,
          });

          logger.error('waitForSubmission', result.status, HTTP_ERROR_MAP[result.status]);

          return null;
        }

        const data = result.body.data;

        if (result.status == 204) {
          await next({ status: '[Luogu] Waiting' });
          continue;
        }

        if (result.status == 200 && !data) {
          return await end({
            error: true,
            id,
            status: 'Judgement Failed',
            message: 'Failed to fetch submission details.',
          });
        }

        if (data.compile && data.compile.success === false) {
          return await end({
            error: true,
            id,
            status: 'Compile Error',
            message: data.compile.message,
          });
        }

        if (!data.judge?.subtasks) continue;

        const finishedTestCases = Object.entries(data.judge.subtasks)
          .map(o => o[1])
          .reduce(
            (acc, sub) =>
              acc +
              Object.entries(sub.cases)
                .map(o => o[1])
                .filter(test => test.status >= 2).length,
            0
          );

        await next({
          status: `[Luogu] Judging (${finishedTestCases} judged)`,
        });

        if (data.status < 2) continue;

        logger.info('RecordID:', id, 'done');

        let details = '';

        details += '<tests>';

        if (data.judge.subtasks.length === 1) {
          details += Object.entries(data.judge.subtasks[0].cases)
            .map(o => o[1])
            .map(buildLuoguTestCaseInfoBlock)
            .join('\n');
        } else {
          details += Object.entries(data.judge.subtasks)
            .map(o => o[1])
            .map(
              (subtask, index) =>
                `<subtask num="${index}" info="${STATUS_MAP[subtask.status]}" time="${subtask.time || -1}" memory="${
                  subtask.memory || -1
                }" score="${subtask.score || ''}">${Object.entries(subtask.cases)
                  .map(o => o[1])
                  .map(buildLuoguTestCaseInfoBlock)
                  .join('\n')}</subtask>`
            )
            .join('\n');
        }

        details += '</tests>';

        return await end({
          id,
          status: STATUS_MAP[data.judge.status],
          score:
            STATUS_MAP[data.judge.status] === 'Accepted'
              ? 100
              : // Workaround for UOJ feature
                Math.min(97, data.judge.score),
          time: data.judge.time,
          memory: data.judge.memory,
          details,
        });
      } catch (e) {
        logger.error(e);

        fail++;
      }
    }
    return await end({
      error: true,
      id,
      status: 'Judgement Failed',
      message: 'Failed to fetch submission details.',
    });
  }

  async judge(id, pid, lang, code, judge_time, config, request) {
    const next = payload =>
      request('/submit', {
        'update-status': 1,
        fetch_new: 0,
        id,
        status: payload.status || (payload.test_id ? `Judging Test #${payload.test_id}` : 'Judging'),
      });

    const end = payload => {
      if (payload.error) {
        return request('/submit', {
          submit: 1,
          fetch_new: 0,
          id,
          result: JSON.stringify({
            status: 'Judged',
            score: 0,
            error: payload.status,
            details:
              payload.details ||
              '<div>' +
                `<info-block>ID = ${payload.id || 'None'}</info-block>` +
                `<error>${htmlspecialchars(payload.message)}</error>` +
                '</div>',
          }),
          judge_time,
        });
      }

      return request('/submit', {
        submit: 1,
        fetch_new: 0,
        id,
        result: JSON.stringify({
          status: 'Judged',
          score: payload.score,
          time: payload.time,
          memory: payload.memory,
          details:
            payload.details ||
            '<div>' +
              `<info-block>REMOTE_SUBMISSION_ID = ${payload.id || 'None'}\nVERDICT = ${payload.status}</info-block>` +
              '</div>',
        }),
        judge_time,
      });
    };

    try {
      const rid = await this.submitProblem(pid, lang, code, id, next, end);

      if (!rid) return;

      await this.waitForSubmission(rid, next, end);
    } catch (e) {
      logger.error(e);

      await end({
        error: true,
        status: 'Judgement Failed',
        message: e.message,
      });
    }
  }
}

export function getAccountInfoFromEnv() {
  const { LUOGU_API_USERNAME, LUOGU_API_PASSWORD, LUOGU_API_ENDPOINT = 'https://open-v1.lgapi.cn' } = process.env;

  if (!LUOGU_API_USERNAME || !LUOGU_API_PASSWORD) return null;

  const account = {
    type: 'luogu-api',
    username: LUOGU_API_USERNAME,
    password: LUOGU_API_PASSWORD,
    endpoint: LUOGU_API_ENDPOINT,
  };

  return account;
}
