<?php
  $iaNos = $_POST["ia_no"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];

  /* If a complete form is given, submit and update all IA allotments. */
  if (assigned($iaNos) && assigned($soNos) && assigned($brandCodes) && assigned($modelNos) && assigned($qtys)) {
    $queries = array();

    $whereClause = "";

    $whereClause = join(" OR ", array_map(function ($iaNo, $soNo, $brandCode, $modelNo) {
      return "
        ia_no=\"$iaNo\" AND
        so_no=\"$soNo\" AND
        brand_code=\"$brandCode\" AND
        model_no=\"$modelNo\"
      ";
    }, $iaNos, $soNos, $brandCodes, $modelNos));
    array_push($queries, "DELETE FROM `so_allotment` WHERE $whereClause");

    $values = array();

    for ($i = 0; $i < count($iaNos); $i++) {
      $iaNo = $iaNos[$i];
      $soNo = $soNos[$i];
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $qty = $qtys[$i];

      if ($qty > 0) {
        array_push($values, "(\"$iaNo\", \"\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")");
      }
    }

    if (count($values) > 0) {
      array_push($queries, "
        INSERT INTO
          `so_allotment`
            (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
          VALUES
      " . join(", ", $values));
    }

    execute($queries);

    header("Location: " . SALES_ALLOTMENT_REPORT_CUSTOMER_URL);
  }

  $filterIaNos = $_GET["filter_ia_no"];
  $filterDebtorCodes = $_GET["filter_debtor_code"];
  $filterSONos = $_GET["filter_so_no"];

  $whereClause = "";

  if (assigned($filterIaNos) && count($filterIaNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.ia_no=\"$i\""; }, $filterIaNos)) . ")";
  }

  $whereSoAllotmentClause = "
    AND (x.ia_no IS NULL
  ";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereSoAllotmentClause = $whereSoAllotmentClause . "
      OR (" . join(" AND ", array_map(function ($i) { return "y.debtor_code!=\"$i\""; }, $filterDebtorCodes)) . ")";
  }

  if (assigned($filterSONos) && count($filterSONos) > 0) {
    $whereSoAllotmentClause = $whereSoAllotmentClause . "
      OR (" . join(" AND ", array_map(function ($i) { return "y.so_no!=\"$i\""; }, $filterSONos)) . ")";
  }

  $whereSoAllotmentClause = $whereSoAllotmentClause . ")";

  $results = query("
    SELECT
      b.creditor_code                                                       AS `creditor_code`,
      c.creditor_name_eng                                                        AS `creditor_name`,
      a.ia_no                                                               AS `ia_no`,
      DATE_FORMAT(b.ia_date, '%d-%m-%Y')                                    AS `date`,
      a.ia_index                                                            AS `index`,
      d.code                                                                AS `brand_code`,
      d.name                                                                AS `brand_name`,
      a.model_no                                                            AS `model_no`,
      a.qty                                                                 AS `qty`,
      a.qty - IFNULL(e.qty_allotted, 0)                                     AS `qty_available`,
      GREATEST(IFNULL(f.qty_on_hand, 0) - IFNULL(g.qty_on_reserve, 0), 0)   AS `qty_on_hand_available`
    FROM
      `ia_model` AS a
    LEFT JOIN
      `ia_header` AS b
    ON a.ia_no=b.ia_no
    LEFT JOIN
      `cu_ap`.`creditor` AS c
    ON b.creditor_code=c.creditor_code
    LEFT JOIN
      `brand` AS d
    ON a.brand_code=d.code
    LEFT JOIN
      (SELECT
        ia_no                 AS `ia_no`,
        brand_code            AS `brand_code`,
        model_no              AS `model_no`,
        SUM(qty)              AS `qty_allotted`
      FROM
        `so_allotment` AS x
      LEFT JOIN
        `so_header` AS y
      ON x.so_no=y.so_no
      WHERE
        x.ia_no!=\"\"
        $whereSoAllotmentClause
      GROUP BY
        ia_no, brand_code, model_no) AS e
    ON a.ia_no=e.ia_no AND a.brand_code=e.brand_code AND a.model_no=e.model_no
    LEFT JOIN
      (SELECT
        warehouse_code, brand_code, model_no, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        warehouse_code, brand_code, model_no) AS f
    ON b.warehouse_code=f.warehouse_code AND a.brand_code=f.brand_code AND a.model_no=f.model_no
    LEFT JOIN
      (SELECT
        warehouse_code, brand_code, model_no, SUM(qty) AS `qty_on_reserve`
      FROM
        `so_allotment`
      WHERE
        ia_no=\"\"
      GROUP BY
        warehouse_code, brand_code, model_no) AS g
    ON b.warehouse_code=g.warehouse_code AND a.brand_code=g.brand_code AND a.model_no=g.model_no
    WHERE
      b.status=\"SAVED\"
      $whereClause
    ORDER BY
      b.creditor_code ASC,
      a.ia_no ASC,
      a.ia_index ASC,
      a.model_no ASC
  ");

  $iaResults = array();

  foreach ($results as $model) {
    $creditorCode = $model["creditor_code"];
    $creditorName = $model["creditor_name"];
    $iaNo = $model["ia_no"];
    $date = $model["date"];
    $brandCode = $model["brand_code"];
    $modelNo = $model["model_no"];

    $arrayPointer = &$iaResults;

    if (!isset($arrayPointer[$creditorCode])) {
      $arrayPointer[$creditorCode] = array();
      $arrayPointer[$creditorCode]["name"] = $creditorName;
      $arrayPointer[$creditorCode]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$creditorCode]["models"];

    if (!isset($arrayPointer[$iaNo])) {
      $arrayPointer[$iaNo] = array();
      $arrayPointer[$iaNo]["date"] = $date;
      $arrayPointer[$iaNo]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$iaNo]["models"];

    array_push($arrayPointer, $model);
  }

  $iaModels = array();

  foreach ($results as $model) {
    $iaNo = $model["ia_no"];
    $brandCode = $model["brand_code"];
    $modelNo = $model["model_no"];

    $arrayPointer = &$iaModels;

    if (!isset($arrayPointer[$iaNo])) {
      $arrayPointer[$iaNo] = array();
    }
    $arrayPointer = &$arrayPointer[$iaNo];

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo];

    $arrayPointer = $model;
  }

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "b.debtor_code=\"$i\""; }, $filterDebtorCodes)) . ")";
  }

  if (assigned($filterSONos) && count($filterSONos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.so_no=\"$i\""; }, $filterSONos)) . ")";
  }

  $results = query("
    SELECT
      b.debtor_code                       AS `debtor_code`,
      IFNULL(c.english_name, 'Unknown')   AS `debtor_name`,
      a.so_no                             AS `so_no`,
      b.id                                AS `so_id`,
      DATE_FORMAT(b.so_date, '%d-%m-%Y')  AS `date`,
      b.discount                          AS `discount`,
      b.currency_code                     AS `currency_code`,
      b.exchange_rate                     AS `exchange_rate`,
      b.tax                               AS `tax`,
      b.priority                          AS `priority`,
      a.brand_code                        AS `brand_code`,
      a.model_no                          AS `model_no`,
      a.qty                               AS `qty_order`,
      a.qty_outstanding                   AS `qty_outstanding`,
      a.price                             AS `price`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON b.debtor_code=c.code
    WHERE
      a.qty_outstanding > 0 AND b.status=\"CONFIRMED\"
      $whereClause
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC,
      b.so_date ASC
  ");

  $soModels = array();

  foreach ($results as $model) {
    $brandCode = $model["brand_code"];
    $modelNo = $model["model_no"];
    $soNo = $model["so_no"];

    $arrayPointer = &$soModels;

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo];

    if (!isset($arrayPointer[$soNo])) {
      $arrayPointer[$soNo] = array();
    }
    $arrayPointer = &$arrayPointer[$soNo];

    $arrayPointer = $model;
  }

  $results = query("
    SELECT
      IFNULL(b.do_no, '')     AS `do_no`,
      a.ia_no                 AS `ia_no`,
      a.so_no                 AS `so_no`,
      a.brand_code            AS `brand_code`,
      a.model_no              AS `model_no`,
      a.qty                   AS `qty`
    FROM
      `so_allotment` AS a
    LEFT JOIN
      (SELECT
        x.do_no           AS `do_no`,
        x.ia_no           AS `ia_no`,
        x.so_no           AS `so_no`,
        x.brand_code      AS `brand_code`,
        x.model_no        AS `model_no`,
        x.qty             AS `qty`
      FROM
        `sdo_model` AS x
      LEFT JOIN
        `sdo_header` AS y
      ON x.do_no=y.do_no
      WHERE
        y.status=\"SAVED\") AS b
    ON
      a.ia_no=b.ia_no AND
      a.so_no=b.so_no AND
      a.brand_code=b.brand_code AND
      a.model_no=b.model_no AND
      a.qty=b.qty
    ORDER BY
      a.ia_no ASC,
      a.brand_code ASC,
      a.model_no ASC,
      a.so_no ASC
  ");

  $allotments = array();

  foreach ($results as $allotment) {
    $iaNo = $allotment["ia_no"];
    $brandCode = $allotment["brand_code"];
    $modelNo = $allotment["model_no"];
    $soNo = $allotment["so_no"];

    $arrayPointer = &$allotments;

    if (!isset($arrayPointer[$iaNo])) {
      $arrayPointer[$iaNo] = array();
    }
    $arrayPointer = &$arrayPointer[$iaNo];

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo];

    if (!isset($arrayPointer[$soNo])) {
      $arrayPointer[$soNo] = array();
    }
    $arrayPointer = &$arrayPointer[$soNo];

    $arrayPointer = $allotment;
  }

  $ias = query("
    SELECT
      ia_no AS `ia_no`
    FROM
      `ia_header`
    WHERE
      status=\"SAVED\"
    ORDER BY
      ia_no ASC
  ");

  $debtors = query("
    SELECT DISTINCT
      c.code            AS `code`,
      c.english_name    AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                 AS `so_no`,
        SUM(qty_outstanding)  AS `qty_outstanding`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"CONFIRMED\" AND b.qty_outstanding > 0
    ORDER BY
      c.code ASC
  ");

  $soNos = query("
    SELECT DISTINCT
      a.so_no            AS `so_no`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                 AS `so_no`,
        SUM(qty_outstanding)  AS `qty_outstanding`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    WHERE
      a.status=\"CONFIRMED\" AND b.qty_outstanding > 0
    ORDER BY
      a.so_no ASC
  ");
?>
