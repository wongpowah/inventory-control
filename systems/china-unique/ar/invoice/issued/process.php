<?php
  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $invoiceIds = $_POST["invoice_id"];

  if (assigned($action) && assigned($invoiceIds) && count($invoiceIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $invoiceIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $invoiceIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $invoiceIds));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `ar_inv_item` AS a LEFT JOIN `ar_inv_header` AS b ON a.invoice_no=b.invoice_no WHERE $modelWhereClause");
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_inv_header` AS b ON a.invoice_no=b.invoice_no WHERE $modelWhereClause");
      array_push($queries, "DELETE a, c FROM `ar_credit_note` AS a LEFT JOIN `ar_inv_header` AS b ON a.invoice_no=b.invoice_no LEFT JOIN `ar_settlement` AS c ON a.credit_note_no=c.credit_note_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `ar_inv_header` WHERE $headerWhereClause");
    } else if ($action === "cancel") {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_inv_header` AS b ON a.invoice_no=b.invoice_no WHERE $modelWhereClause");
      array_push($queries, "DELETE a, c FROM `ar_credit_note` AS a LEFT JOIN `ar_inv_header` AS b ON a.invoice_no=b.invoice_no LEFT JOIN `ar_settlement` AS c ON a.credit_note_no=c.credit_note_no WHERE $modelWhereClause");
      array_push($queries, "UPDATE `ar_inv_header` SET status=\"CANCELLED\" WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_INVOICE_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $whereClause = "";

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
      a.id                                                                              AS `id`,
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\")                                         AS `date`,
      b.count                                                                           AS `count`,
      a.invoice_no                                                                      AS `invoice_no`,
      IFNULL(c.english_name, \"Unknown\")                                               AS `debtor_name`,
      a.currency_code                                                                   AS `currency_code`,
      DATE_FORMAT(a.maturity_date, \"%d-%m-%Y\")                                        AS `maturity_date`,
      IFNULL(b.amount, 0)                                                               AS `amount`,
      IFNULL(b.amount, 0) - IFNULL(d.settled_amount, 0) + IFNULL(e.credited_amount, 0)  AS `outstanding`
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
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.invoice_date DESC
  ");
?>
