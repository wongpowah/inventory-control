<?php
  $periods = array_map(function ($p) { return $p["period"]; }, query("
    SELECT DISTINCT
      DATE_FORMAT(do_date, \"%Y-%m\")           AS `period`
    FROM
      `sdo_header`
    WHERE
      status=\"POSTED\"
    UNION
    SELECT DISTINCT
      DATE_FORMAT(stock_out_date, \"%Y-%m\")    AS `period`
    FROM
      `stock_out_header`
    WHERE
      status=\"POSTED\"
    ORDER BY
      period DESC
  "));

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $period = assigned($_GET["period"]) ? $_GET["period"] : (count($periods) > 0 ? $periods[0] : "");
  $filterDebtorCodes = $_GET["filter_debtor_code"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "all";

  $doWhereClause = "";
  $stockOutWhereClause = "";
  $stockInReturnWhereClause = "";
  $invoiceWhereClause = "";
  $currentInvoiceWhereClause = "";

  if (assigned($period)) {
    $doWhereClause = $doWhereClause . "
      AND (e.invoice_settlement IS NULL OR e.invoice_settlement = 0)
      AND DATE_FORMAT(a.do_date, \"%Y-%m\") <= \"$period\"";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND (e.invoice_settlement IS NULL OR e.invoice_settlement = 0)
      AND DATE_FORMAT(a.stock_out_date, \"%Y-%m\") <= \"$period\"";
    $stockInReturnWhereClause = $stockInReturnWhereClause . "
      AND (e.invoice_settlement IS NULL OR e.invoice_settlement = 0)
      AND DATE_FORMAT(a.stock_in_date, \"%Y-%m\") <= \"$period\"";
    $invoiceWhereClause = $invoiceWhereClause . "
      y.invoice_date < \"$period-01\"";
    $currentInvoiceWhereClause = $currentInvoiceWhereClause . "
      DATE_FORMAT(y.invoice_date, \"%Y-%m\")=\"$period\"";
  }

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $doWhereClause = $doWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
    $stockInReturnWhereClause = $stockInReturnWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.creditor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  if ($showMode === "issued_only") {
    $doWhereClause = $doWhereClause . "
      AND d.invoice_amounts IS NOT NULL";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND d.invoice_amounts IS NOT NULL";
    $stockInReturnWhereClause = $stockInReturnWhereClause . "
      AND d.invoice_amounts IS NOT NULL";
  }

  $incomeHeaders = array();

  function getColumns($soNo, $dateC, $doIdC, $doNoC, $stockOutIdC, $stockOutNoC, $stockInIdC, $stockInNoC, $clientCode) {
    return "
      $soNo                                                                               AS `so_no`,
      $dateC                                                                              AS `date_`,
      DATE_FORMAT($dateC, \"%d-%m-%Y\")                                                   AS `date`,
      DATE_FORMAT($dateC, \"%Y-%m\")                                                      AS `period`,
      $doIdC                                                                              AS `do_id`,
      $doNoC                                                                              AS `do_no`,
      $stockOutIdC                                                                        AS `stock_out_id`,
      $stockOutNoC                                                                        AS `stock_out_no`,
      $stockInIdC                                                                         AS `stock_in_id`,
      $stockInNoC                                                                         AS `stock_in_no`,
      $clientCode                                                                         AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")                                                 AS `debtor_name`,
      IFNULL(b.qty, 0)                                                                    AS `qty`,
      a.currency_code                                                                     AS `currency`,
      ROUND(IFNULL(b.amount, 0) * (100 - a.discount) / 100, 2)                            AS `amount`,
      ROUND(IFNULL(b.amount, 0) * (100 - a.discount) / 100 - IFNULL(e.invoice_sum, 0), 2) AS `pending`,
      ROUND(IFNULL(b.amount, 0) * (100 - a.discount) / (100 + a.tax), 2)                  AS `net`,
      ROUND(IFNULL(b.cost, 0), 2)                                                         AS `cost`,
      CASE
        WHEN d.invoice_settlement>0 THEN \"FULL\"
        WHEN d.invoice_settlement=0 THEN \"PARTIAL\"
        ELSE \"PENDING\"
      END                                                                                 AS `settlement`,
      IFNULL(d.invoice_amounts, \"\")                                                     AS `invoice_amounts`,
      IFNULL(d.invoice_dates, \"\")                                                       AS `invoice_dates`,
      IFNULL(d.invoice_nos, \"\")                                                         AS `invoice_nos`,
      IFNULL(d.invoice_ids, \"\")                                                         AS `invoice_ids`
    ";
  }

  function joinModelTable($as, $link, $whereClause, $negateValues = false) {
    $prefix = $negateValues ? "-" : "";
    return "
      LEFT JOIN
        (SELECT
          x.header_no                           AS `$link`,
          $prefix SUM(x.qty)                    AS `qty`,
          $prefix SUM(x.qty * x.price)          AS `amount`,
          $prefix SUM(x.qty * x.cost_average)   AS `cost`
        FROM
          `transaction` AS x
        LEFT JOIN
          `model` AS y
        ON x.brand_code=y.brand_code AND x.model_no=y.model_no
        WHERE
          $whereClause
        GROUP BY
          x.header_no) AS $as
      ON a.$link=$as.$link
    ";
  }

  function joinInvoiceTable($as, $columnName, $whereClause) {
    return "
      LEFT JOIN
        (SELECT
          x.$columnName                                           AS `$columnName`,
          GROUP_CONCAT(DATE_FORMAT(y.invoice_date, \"%d-%m-%Y\")) AS `invoice_dates`,
          GROUP_CONCAT(y.id)                                      AS `invoice_ids`,
          GROUP_CONCAT(x.invoice_no)                              AS `invoice_nos`,
          GROUP_CONCAT(x.amount)                                  AS `invoice_amounts`,
          SUM(IF(x.settlement=\"FULL\",1, 0))                     AS `invoice_settlement`
        FROM
          `ar_inv_item` AS x
        LEFT JOIN
          `ar_inv_header` AS y
        ON x.invoice_no=y.invoice_no
        WHERE
          $whereClause
        GROUP BY
          x.$columnName) AS $as
      ON a.$columnName=$as.$columnName
    ";
  }

  function joinInvoiceSumTable($as, $columnName, $whereClause) {
    return "
      LEFT JOIN
        (SELECT
          x.$columnName                       AS `$columnName`,
          SUM(x.amount)                       AS `invoice_sum`,
          SUM(IF(x.settlement=\"FULL\",1, 0)) AS `invoice_settlement`
        FROM
          `ar_inv_item` AS x
        LEFT JOIN
          `ar_inv_header` AS y
        ON x.invoice_no=y.invoice_no
        WHERE
          $whereClause
        GROUP BY
          x.$columnName) AS $as
      ON a.$columnName=$as.$columnName
    ";
  }

  $results = query("
    SELECT
      " . getColumns("IFNULL(b2.so_nos, \"\")", "a.do_date", "a.id", "a.do_no", "\"\"", "\"\"", "\"\"", "\"\"", "a.debtor_code") . "
    FROM
      `sdo_header` AS a
    " . joinModelTable("b", "do_no", "x.transaction_code=\"S2\"") . "
    LEFT JOIN
      (SELECT
        do_no                        AS `do_no`,
        GROUP_CONCAT(DISTINCT so_no) AS `so_nos`
      FROM
        `sdo_model`
      GROUP BY
        do_no) AS b2
    ON a.do_no=b2.do_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    " . joinInvoiceTable("d", "do_no", $currentInvoiceWhereClause) . "
    " . joinInvoiceSumTable("e", "do_no", $invoiceWhereClause) . "
    WHERE
      a.status=\"POSTED\" AND
      IFNULL(b.qty, 0) != 0
      $doWhereClause
    UNION
    SELECT
      " . getColumns("a.transaction_code", "a.stock_out_date", "\"\"", "\"\"", "a.id", "a.stock_out_no", "\"\"", "\"\"", "a.debtor_code") . "
    FROM
      `stock_out_header` AS a
    " . joinModelTable("b", "stock_out_no", "x.transaction_code=\"S1\"") . "
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    " . joinInvoiceTable("d", "stock_out_no", $currentInvoiceWhereClause) . "
    " . joinInvoiceSumTable("e", "stock_out_no", $invoiceWhereClause) . "
    WHERE
      a.status=\"POSTED\" AND
      a.transaction_code=\"S1\"
      $stockOutWhereClause
    UNION
    SELECT
      " . getColumns("a.transaction_code", "a.stock_in_date", "\"\"", "\"\"", "\"\"", "\"\"", "a.id", "a.stock_in_no", "a.creditor_code") . "
    FROM
      `stock_in_header` AS a
    " . joinModelTable("b", "stock_in_no", "x.transaction_code=\"R3\"", true) . "
    LEFT JOIN
      `debtor` AS c
    ON a.creditor_code=c.code
    " . joinInvoiceTable("d", "stock_in_no", $currentInvoiceWhereClause) . "
    " . joinInvoiceSumTable("e", "stock_in_no", $invoiceWhereClause) . "
    WHERE
      a.status=\"POSTED\" AND
      a.transaction_code=\"R3\"
      $stockInReturnWhereClause
    ORDER BY
      debtor_code ASC,
      date_ ASC
  ");

  foreach ($results as $incomeHeader) {
    $currency = $incomeHeader["currency"];

    $arrayPointer = &$incomeHeaders;

    if (!isset($arrayPointer[$currency])) {
      $arrayPointer[$currency] = array();
    }
    $arrayPointer = &$arrayPointer[$currency];

    array_push($arrayPointer, $incomeHeader);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    WHERE
      a.status=\"POSTED\"
    UNION
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `stock_out_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    WHERE
      a.status=\"POSTED\"
    ORDER BY
      code ASC
  ");

  $pIndex = array_search($period, $periods);
  $previousPeriod = $pIndex === FALSE || ($pIndex + 1 > count($periods)) ? "" : $periods[$pIndex + 1];
?>
