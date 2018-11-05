CREATE TABLE `model` (
  `id`                      INT(12)         NOT NULL AUTO_INCREMENT,
  `brand_code`              VARCHAR(30)     NOT NULL,
  `model_no`                VARCHAR(30)     NOT NULL,
  `description`             TEXT,
  `cost_pri`                DECIMAL(16,6)   NOT NULL,
  `cost_pri_currency_code`  VARCHAR(30)     NOT NULL,
  `cost_sec`                DECIMAL(16,6)   NOT NULL,
  `cost_sec_currency_code`  VARCHAR(30)     NOT NULL,
  `cost_average`            DECIMAL(16,6)   NOT NULL,
  `wholesale_normal`        DECIMAL(16,6)   NOT NULL,
  `retail_normal`           DECIMAL(16,6)   NOT NULL,
  `wholesale_special`       DECIMAL(16,6)   NOT NULL,
  `retail_special`          DECIMAL(16,6)   NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`brand_code`,`model_no`)
);
