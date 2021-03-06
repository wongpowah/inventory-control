<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $ids = $_GET["id"];
  $filterWarehouseCodes = $_GET["filter_warehouse_code"];
  $filterModelNos = $_GET["filter_model_no"];

  $whereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids)) . ")";
  }

  if (assigned($filterWarehouseCodes) && count($filterWarehouseCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.warehouse_code=\"$i\""; }, $filterWarehouseCodes)) . ")";
  }

  if (assigned($filterModelNos) && count($filterModelNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.model_no=\"$i\""; }, $filterModelNos)) . ")";
  }

  $results = query("
    SELECT
      a.brand_code                                AS `brand_code`,
      c.name                                      AS `brand_name`,
      b.id                                        AS `model_id`,
      a.model_no                                  AS `model_no`,
      a.warehouse_code                            AS `warehouse_code`,
      a.qty                                       AS `qty`,
      f.qty_on_loan                               AS `qty_on_loan`,
      f.qty_on_borrow                             AS `qty_on_borrow`,
      d.qty_on_reserve                            AS `qty_on_reserve`,
      b.cost_average                              AS `cost_average`,
      ROUND(a.qty * b.cost_average, 2)            AS `subtotal`
    FROM
      `stock` AS a
    LEFT JOIN
      `model` AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      (SELECT
        h.warehouse_code  AS `warehouse_code`,
        m.brand_code      AS `brand_code`,
        m.model_no        AS `model_no`,
        SUM(m.qty)        AS `qty_on_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        h.status=\"SAVED\" AND
        m.ia_no=\"\"
      GROUP BY
        h.warehouse_code, m.brand_code, m.model_no) AS d
    ON a.warehouse_code=d.warehouse_code AND a.brand_code=d.brand_code AND a.model_no=d.model_no
    LEFT JOIN
      (SELECT
        brand_code,
        model_no,
        warehouse_code,
        SUM(IF(transaction_code=\"S7\", qty, 0)) - SUM(IF(transaction_code=\"R8\", qty, 0)) AS `qty_on_loan`,
        SUM(IF(transaction_code=\"R7\", qty, 0)) - SUM(IF(transaction_code=\"S8\", qty, 0)) AS `qty_on_borrow`
      FROM
        `transaction`
      GROUP BY
        brand_code, model_no, warehouse_code) AS f
    ON a.brand_code=f.brand_code AND a.model_no=f.model_no AND a.warehouse_code=f.warehouse_code
    WHERE
      a.qty > 0
      $whereClause
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC,
      a.warehouse_code ASC
  ");

  $stocks = array();

  foreach ($results as $stock) {
    $brandCode = $stock["brand_code"];
    $brandName = $stock["brand_name"];
    $modelNo = $stock["model_no"];
    $modelId = $stock["model_id"];

    $arrayPointer = &$stocks;

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
      $arrayPointer[$brandCode]["name"] = $brandName;
      $arrayPointer[$brandCode]["stocks"] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode]["stocks"];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
      $arrayPointer[$modelNo]["id"] = $modelId;
      $arrayPointer[$modelNo]["stocks"] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo]["stocks"];

    array_push($arrayPointer, $stock);
  }

  foreach ($stocks as $bCode => &$b) {
    foreach ($b["stocks"] as $mCode => &$m) {
      if (count($m["stocks"]) == 1) {
        unset($stocks[$bCode]["stocks"][$mCode]);
      }
    }

    if (count($b["stocks"]) === 0) {
      unset($stocks[$bCode]);
    }
  }


  $warehouseWhereClause = "";
  $modelWhereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $warehouseWhereClause = $warehouseWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids)) . ")";
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids)) . ")";
  }

  if (assigned($filterWarehouseCodes) && count($filterWarehouseCodes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.warehouse_code=\"$i\""; }, $filterWarehouseCodes)) . ")";
  }

  $warehouses = query("
    SELECT DISTINCT
      b.code AS `warehouse_code`,
      b.name AS `warehouse_name`
    FROM
      `stock` AS a
    LEFT JOIN
      `warehouse` AS b
    ON a.warehouse_code=b.code
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    WHERE
      a.qty > 0
      $warehouseWhereClause
    ORDER BY
      b.code ASC
  ");

  $models = query("
    SELECT DISTINCT
      b.model_no
    FROM
      `stock` AS a
    LEFT JOIN
      `model` AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      `warehouse` AS d
    ON a.warehouse_code=d.code
    WHERE
      a.qty > 0
      $modelWhereClause
    ORDER BY
      b.model_no ASC
  ");
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo REPORT_MULTIPLE_WAREHOUSE_TITLE; ?></div>
      <form>
        <?php
          if (assigned($ids) && count($ids) > 0) {
            echo join(array_map(function ($id) {
              return "<input type=\"hidden\" name=\"id[]\" value=\"$id\" />";
            }, $ids));
          }
        ?>
        <table id="brand-input" class="web-only">
          <tr>
            <th>Warehouse:</th>
            <th>Model No.:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_warehouse_code[]" multiple>
                <?php
                  foreach ($warehouses as $warehouse) {
                    $warehouseCode = $warehouse["warehouse_code"];
                    $warehouseName = $warehouse["warehouse_name"];
                    $selected = assigned($filterWarehouseCodes) && in_array($warehouseCode, $filterWarehouseCodes) ? "selected" : "";
                    echo "<option value=\"$warehouseCode\" $selected>$warehouseCode - $warehouseName</option>";
                  }
                ?>
              </select>
            </td>
            <td>
              <select name="filter_model_no[]" multiple>
                <?php
                  foreach ($models as $model) {
                    $modelNo = $model["model_no"];
                    $selected = assigned($filterModelNos) && in_array($modelNo, $filterModelNos) ? "selected" : "";
                    echo "<option value=\"$modelNo\" $selected>$modelNo</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($stocks) > 0) : ?>
        <?php foreach ($stocks as $brandCode => &$brand) : ?>
          <div class="brand-client">
            <h4><?php echo $brandCode . " - " . $brand["name"]; ?></h4>
            <table class="brand-results">
              <colgroup>
                <col>
                <col style="width: 80px;">
                <col style="width: 50px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Model No.</th>
                  <th class="number">Avg. Cost</th>
                  <th class="number">W.C.</th>
                  <th class="number">Qty</th>
                  <th class="number">Reserved</th>
                  <th class="number">Available</th>
                  <th class="number">Subtotal</th>
                  <th class="number">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $brandStocks = $brand["stocks"];

                  $totalQty = 0;
                  $totalQtyOnReserve = 0;
                  $totalQtyAvailable = 0;
                  $totalAmt = 0;

                  foreach ($brandStocks as $modelNo => &$model) {
                    $modelId = $model["id"];
                    $modelStocks = $model["stocks"];
                    $stockCount = count($modelStocks);

                    for ($i = 0; $i < $stockCount; $i++) {
                      $modelStock = $modelStocks[$i];
                      $qty = $modelStock["qty"];
                      $qtyOnReserve = $modelStock["qty_on_reserve"];
                      $qtyAvailable = $qty - $qtyOnReserve;
                      $warehouseCode = $modelStock["warehouse_code"];
                      $costAverage = $modelStock["cost_average"];
                      $subtotal = $modelStock["subtotal"];

                      $totalQty += $qty;
                      $totalQtyOnReserve += $qtyOnReserve;
                      $totalQtyAvailable += $qtyAvailable;
                      $totalAmt += $subtotal;
                      $total = array_sum(array_map(function ($md) { return $md["subtotal"]; }, $modelStocks));

                      $modelColumns = $i === 0 ? "
                        <td rowspan=\"" . $stockCount . "\" title=\"$modelNo\">
                          <a class=\"link\" href=\"" . DATA_MODEL_MODEL_DETAIL_URL . "?id=$modelId\">$modelNo</a>
                        </td>
                        <td rowspan=\"" . $stockCount . "\" title=\"$costAverage\" class=\"number\">
                        " . number_format($costAverage, 2) . "
                        </td>
                      " : "";

                      $qtyColumns = $i === 0 ? "
                        <td rowspan=\"" . $stockCount . "\" title=\"$total\" class=\"number\">" . number_format($total, 2) . "</td>
                      " : "";

                      echo "
                        <tr>
                          $modelColumns
                          <td title=\"$warehouseCode\" class=\"number\">$warehouseCode</td>
                          <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                          <td title=\"$qtyOnReserve\" class=\"number\">" . number_format($qtyOnReserve) . "</td>
                          <td title=\"$qtyAvailable\" class=\"number\">" . number_format($qtyAvailable) . "</td>
                          <td title=\"$subtotal\" class=\"number\">" . number_format($subtotal, 2) . "</td>
                          $qtyColumns
                        </tr>
                      ";
                    }
                  }
                ?>
                <tr>
                  <th></th>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($totalQtyOnReserve); ?></th>
                  <th class="number"><?php echo number_format($totalQtyAvailable); ?></th>
                  <th class="number"></th>
                  <th class="number"><?php echo number_format($totalAmt, 2); ?></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="brand-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
