import fs from 'fs-extra';
import superagent from 'superagent';
import path from 'node:path';
import child from 'node:child_process';

import Luogu, { getAccountInfoFromEnv } from './luogu.js';
import Logger from './utils/logger.js';
import sleep from './utils/sleep.js';
import * as TIME from './utils/time.js';
import htmlspecialchars from './utils/htmlspecialchars.js';

const logger = new Logger('daemon');

async function daemon(config) {
  function request(url, data) {
    const req_url = `${config.server_url}/judge${url}`;

    logger.debug('request', req_url, data);

    return superagent
      .post(req_url)
      .set('Content-Type', 'application/x-www-form-urlencoded')
      .send(
        Object.entries({
          judger_name: config.judger_name,
          password: config.password,
          ...data,
        })
          .map(([k, v]) => `${k}=${encodeURIComponent(typeof v === 'string' ? v : JSON.stringify(v))}`)
          .join('&')
      );
  }

  const luogu = new Luogu(getAccountInfoFromEnv());

  logger.info('Daemon started.');

  while (true) {
    try {
      await sleep(TIME.second);

      const { text, error } = await request('/submit');

      if (error) {
        logger.error('/submit', error.message);

        continue;
      }

      if (text.startsWith('Nothing to judge')) {
        logger.debug('Nothing to judge.');

        continue;
      }

      const data = JSON.parse(text);
      const { id, content, judge_time } = data;
      const config = Object.fromEntries(content.config);
      const tmpdir = `/tmp/s2oj_rmj/${id}/`;

      if (config.test_sample_only === 'on') {
        await request('/submit', {
          submit: 1,
          fetch_new: 0,
          id,
          result: JSON.stringify({
            status: 'Judged',
            score: 100,
            time: 0,
            memory: 0,
            details: '<info-block>Sample test is not available.</info-block>',
          }),
          judge_time,
        });

        continue;
      }

      fs.ensureDirSync(tmpdir);

      let code = '';

      try {
        // =========================
        // Download source code
        // =========================

        logger.debug('Downloading source code for ' + id);

        const zipFilePath = path.resolve(tmpdir, 'all.zip');
        const res = request(`/download${content.file_name}`);
        const stream = fs.createWriteStream(zipFilePath);

        res.pipe(stream);

        await new Promise((resolve, reject) => {
          stream.on('finish', resolve);
          stream.on('error', reject);
        });

        // =========================
        // Extract source code
        // =========================

        logger.debug('Extracting source code for ' + id);

        const extractedPath = path.resolve(tmpdir, 'all');

        await new Promise((resolve, reject) => {
          child.exec(`unzip ${zipFilePath} -d ${extractedPath}`, e => {
            if (e) reject(e);
            else resolve(true);
          });
        });

        // =========================
        // Read source code
        // =========================
        logger.debug('Reading source code.', id);

        const sourceCodePath = path.resolve(extractedPath, 'answer.code');

        code = fs.readFileSync(sourceCodePath, 'utf-8');
      } catch (e) {
        await request('/submit', {
          submit: 1,
          fetch_new: 0,
          id,
          result: JSON.stringify({
            status: 'Judged',
            score: 0,
            error: 'Judgement Failed',
            details: `<error>Failed to download and extract source code.</error>`,
          }),
          judge_time,
        });

        logger.error('Failed to download and extract source code.', id, e.message);

        fs.removeSync(tmpdir);

        continue;
      }

      // =========================
      // Start judging
      // =========================

      logger.info('Start judging', id, `(problem ${data.problem_id})`);

      try {
        await luogu.judge(id, config.luogu_pid, config.answer_language, code, judge_time, config, request);
      } catch (err) {
        await request('/submit', {
          submit: 1,
          fetch_new: 0,
          id,
          result: JSON.stringify({
            status: 'Judged',
            score: 0,
            error: 'Judgement Failed',
            details: `<error>${htmlspecialchars(err.message)}</error>`,
          }),
          judge_time,
        });

        logger.error('Judgement Failed.', id, err.message);

        fs.removeSync(tmpdir);

        continue;
      }

      fs.removeSync(tmpdir);
    } catch (err) {
      logger.error(err.message);
    }
  }
}

export default daemon;
