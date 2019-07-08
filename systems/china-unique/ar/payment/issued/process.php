<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $paymentIds = $_POST["payment_id"];

  if (assigned($action) && assigned($paymentIds) && count($paymentIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $paymentIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $paymentIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $paymentIds));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_payment` AS b ON a.payment_no=b.payment_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `ar_payment` WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_PAYMENT_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.payment_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.payment_date <= \"$to\"";
  }

  $paymentHeaders = query("
    SELECT
      a.id                                              AS `id`,
      DATE_FORMAT(a.payment_date, \"%d-%m-%Y\")         AS `date`,
      a.payment_no                                      AS `payment_no`,
      a.debtor_code                                     AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")               AS `debtor_name`,
      a.currency_code                                   AS `currency_code`,
      ROUND(a.amount, 2)                                AS `amount`,
      ROUND(a.amount - IFNULL(b.settled_amount, 0), 2)  AS `remaining`
    FROM
      `ar_payment` AS a
    LEFT JOIN
      (SELECT
        payment_no    AS `payment_no`,
        SUM(amount)   AS `settled_amount`
      FROM
        `ar_settlement`
      GROUP BY
        payment_no) AS b
    ON a.payment_no=b.payment_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.payment_date DESC
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `ar_payment` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    ORDER BY
      code ASC
  ");
?>