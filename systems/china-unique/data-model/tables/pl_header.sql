CREATE TABLE `pl_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `pl_no`             VARCHAR(30)     NOT NULL,
  `pl_date`           DATETIME        NOT NULL,
  `debtor_code`       VARCHAR(30)     NOT NULL,
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `discount`          DECIMAL(5,2)    DEFAULT 0,
  `tax`               DECIMAL(5,2)    NOT NULL,
  `warehouse_code`    VARCHAR(30)     NOT NULL,
  `ref_no`            VARCHAR(30)     DEFAULT "",
  `remarks`           TEXT,
  `status`            VARCHAR(30)     DEFAULT "SAVED",
  `paid`              VARCHAR(10)     DEFAULT "FALSE",
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`pl_no`)
);
