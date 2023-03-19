ALTER TABLE
    `problems`
ADD
    `type` varchar(20) NOT NULL DEFAULT 'local';

ALTER TABLE
    `problems`
ADD
    KEY `type` (`type`);
