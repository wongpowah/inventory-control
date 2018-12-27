<?php
  $id = $_GET["id"];

  $doHeader = null;
  $doModels = array();

  /* Only populate the data if an id is given. */
  if (assigned($id)) {
    $doHeader = query("
      SELECT
        a.do_no                                           AS `do_no`,
        DATE_FORMAT(a.do_date, '%d-%m-%Y')                AS `date`,
        IFNULL(b.english_name, 'Unknown')                 AS `customer_name`,
        b.bill_address                                    AS `customer_address`,
        b.contact                                         AS `customer_contact`,
        b.tel                                             AS `customer_tel`,
        CONCAT(a.currency_code, ' @ ', a.exchange_rate)   AS `currency`,
        a.discount                                        AS `discount`,
        a.tax                                             AS `tax`,
        c.name                                            AS `warehouse`,
        a.invoice_no                                      AS `invoice_no`,
        a.remarks                                         AS `remarks`,
        a.status                                          AS `status`
      FROM
        `sdo_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      LEFT JOIN
        `warehouse` AS c
      ON a.warehouse_code=c.code
      WHERE
        a.id=\"$id\"
    ")[0];

    $doModels = query("
      SELECT
        b.name            AS `brand`,
        a.model_no        AS `model_no`,
        a.so_no           AS `so_no`,
        a.price           AS `price`,
        c.cost_average    AS `cost_average`,
        SUM(a.qty)        AS `qty`
      FROM
        `sdo_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `model` AS c
      ON a.brand_code=c.brand_code AND a.model_no=c.model_no
      LEFT JOIN
        `sdo_header` AS d
      ON a.do_no=d.do_no
      WHERE
        d.id=\"$id\"
      GROUP BY
        a.brand_code, a.model_no, a.so_no, a.price, c.cost_average
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");
  }
?>
