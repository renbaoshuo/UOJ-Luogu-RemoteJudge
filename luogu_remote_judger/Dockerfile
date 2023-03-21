FROM node:18.15.0

WORKDIR /opt/s2oj_remote_judger
COPY package*.json ./

RUN npm ci

COPY . .

ENV LANG=C.UTF-8 TZ="Asia/Shanghai"
CMD [ "node", "dist/entrypoint.js" ]
