CREATE TABLE `po_allotment` (
  `id`                INT(12)       NOT NULL AUTO_INCREMENT,
  `ia_no`             VARCHAR(30)   NOT NULL,
  `po_no`             VARCHAR(30)   NOT NULL,
  `brand_code`        VARCHAR(30)   NOT NULL,
  `model_no`          VARCHAR(30)   NOT NULL,
  `qty`               INT(12)       NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`ia_no`, `po_no`, `brand_code`,`model_no`)
);
