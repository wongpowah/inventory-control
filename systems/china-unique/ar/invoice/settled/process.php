<?php
  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $invoiceIds = $_POST["invoice_id"];

  if (assigned($action) && assigned($invoiceIds) && count($invoiceIds) > 0) {
    $queries = array();

    foreach ($invoiceIds as $invoiceId) {
      if ($action !== "print") {
        $invoice = query("SELECT invoice_no FROM `ar_inv_header` WHERE id=\"$invoiceId\"")[0];
        $invoiceNo = assigned($invoice) ? $invoice["invoice_no"] : "";
        array_push($queries, recordInvoiceAction($action . "_invoice", $invoiceNo));
      }
    }

    execute($queries);

    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $invoiceIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $invoiceIds));

    if ($action === "reverse") {
      array_push($queries, "UPDATE `ar_inv_header` SET status=\"SAVED\" WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_INVOICE_PRINTOUT_URL . "?$printoutParams");
      exit();
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
      AND a.invoice_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.invoice_date <= \"$to\"";
  }

  $invoiceHeaders = query("
    SELECT
      a.id                                                                                        AS `id`,
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\")                                                   AS `date`,
      b.count                                                                                     AS `count`,
      a.invoice_no                                                                                AS `invoice_no`,
      a.debtor_code                                                                               AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")                                                         AS `debtor_name`,
      a.currency_code                                                                             AS `currency_code`,
      DATE_FORMAT(a.maturity_date, \"%d-%m-%Y\")                                                  AS `maturity_date`,
      ROUND(IFNULL(b.amount, 0), 2)                                                               AS `amount`,
      ROUND(IFNULL(b.amount, 0) - IFNULL(d.settled_amount, 0) + IFNULL(e.credited_amount, 0), 2)  AS `variance`
    FROM
      `ar_inv_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)                                  AS `count`,
        invoice_no                                AS `invoice_no`,
        SUM(amount)                               AS `amount`
      FROM
        `ar_inv_item`
      GROUP BY
        invoice_no) AS b
    ON a.invoice_no=b.invoice_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    LEFT JOIN
      (SELECT
        invoice_no    AS `invoice_no`,
        SUM(amount)   AS `settled_amount`
      FROM
        `ar_settlement`
      GROUP BY
        invoice_no) AS d
    ON a.invoice_no=d.invoice_no
    LEFT JOIN
      (SELECT
        invoice_no    AS `invoice_no`,
        SUM(amount)   AS `credited_amount`
      FROM
        `ar_credit_note`
      GROUP BY
        invoice_no) AS e
    ON a.invoice_no=e.invoice_no
    WHERE
      a.status=\"SETTLED\"
      $whereClause
    ORDER BY
      a.invoice_date DESC
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `ar_inv_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    WHERE
      a.status=\"SETTLED\"
    ORDER BY
      code ASC
  ");
?>
