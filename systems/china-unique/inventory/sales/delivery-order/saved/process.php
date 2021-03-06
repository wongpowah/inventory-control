<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $filterDebtorCodes = $_GET["filter_debtor_code"];
  $action = $_POST["action"];
  $doIds = $_POST["do_id"];
  $deleteAllotments = $_POST["delete_allotments"];

  if (assigned($action) && assigned($doIds) && count($doIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $doIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $doIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $doIds));
    $postDoNos = array_map(function ($i) { return $i["do_no"]; }, query("SELECT do_no FROM `sdo_header` WHERE $headerWhereClause"));

    if ($action === "delete") {
      array_push($queries, "
        DELETE a, b" . (isset($deleteAllotments) ? ", d" : "") . " FROM
          `sdo_model` AS a
        LEFT JOIN
          `sdo_header` AS b
        ON a.do_no=b.do_no
        LEFT JOIN
          `ia_header` AS c
        ON a.ia_no=c.ia_no
        LEFT JOIN
          `so_allotment` AS d
        ON
          a.ia_no=d.ia_no AND
          b.warehouse_code=IF(d.warehouse_code=\"\", c.warehouse_code, d.warehouse_code) AND
          a.so_no=d.so_no AND
          a.brand_code=d.brand_code AND
          a.model_no=d.model_no
        WHERE
          $modelWhereClause
      ");
    } else if ($action === "post") {
      array_push($queries, "UPDATE `sdo_header` SET status=\"POSTED\" WHERE $headerWhereClause");

      foreach ($postDoNos as $doNo) {
        $queries = concat($queries, onPostSalesDeliveryOrder($doNo));
      }
    } else if ($action === "print") {
      header("Location: " . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?$printoutParams");
      exit();
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.do_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.do_date <= \"$to\"";
  }

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "c.code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  $doHeaders = query("
    SELECT
      a.id                                                                    AS `do_id`,
      DATE_FORMAT(a.do_date, \"%d-%m-%Y\")                                    AS `date`,
      a.do_no                                                                 AS `do_no`,
      a.debtor_code                                                           AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")                                     AS `debtor_name`,
      IFNULL(b.total_qty, 0)                                                  AS `qty`,
      (CASE
        WHEN b.has_provisional > 0  THEN \"provisional\"
        WHEN b.has_incoming > 0     THEN \"incoming\"
        ELSE \"on hand\"
      END)                                                                    AS `item_status`,
      (CASE
        WHEN b.normal_count=b.count   THEN \"normal\"
        WHEN b.special_count=b.count  THEN \"special\"
        WHEN b.end_user_count=b.count THEN \"end user\"
        ELSE \"custom\"
      END)                                                                    AS `price_category`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      (SELECT
        s.do_no                                     AS `do_no`,
        SUM(s.qty)                                  AS `total_qty`,
        SUM(IF(s.price=m.retail_normal, 1, 0))      AS `normal_count`,
        SUM(IF(s.price=m.retail_special, 1, 0))     AS `special_count`,
        SUM(IF(s.price=m.wholesale_special, 1, 0))  AS `end_user_count`,
        COUNT(s.id)                                 AS `count`,
        SUM(s.qty * s.price)                        AS `total_amt`,
        SUM(IF(y.status=\"SAVED\", 1, 0))           AS `has_provisional`,
        SUM(IF(y.status=\"DO\", 1, 0))              AS `has_incoming`
      FROM
        `sdo_model` AS s
      LEFT JOIN
        `model` AS m
      ON s.brand_code=m.brand_code AND s.model_no=m.model_no
      LEFT JOIN
        `ia_header` AS y
      ON s.ia_no=y.ia_no
      GROUP BY
        do_no) AS b
    ON a.do_no=b.do_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.do_date DESC,
      a.do_no DESC
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(c.english_name, \"Unknown\") AS `name`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
    ORDER BY
      a.debtor_code ASC
  ");
?>
