CREATE TABLE `sdo_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `do_no`             VARCHAR(30)     NOT NULL,
  `do_index`          INT(12)         NOT NULL,
  `ia_no`             VARCHAR(30)     NOT NULL,
  `so_no`             VARCHAR(30)     NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `price`             DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`do_no`, `ia_no`, `so_no`, `brand_code`,`model_no`)
);
