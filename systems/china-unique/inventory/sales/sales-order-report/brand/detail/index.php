<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $brandCodes = $_GET["brand_code"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "outstanding_only";

  $whereClause = "";

  if (assigned($brandCodes) && count($brandCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $brandCodes)) . ")";
  }

  if ($showMode == "outstanding_only") {
    $whereClause = $whereClause . "
      AND a.qty_outstanding > 0";
  }

  $results = query("
    SELECT
      DATE_FORMAT(b.so_date, '%d-%m-%Y')                                                AS `date`,
      a.brand_code                                                                      AS `brand_code`,
      c.name                                                                            AS `brand_name`,
      b.id                                                                              AS `so_id`,
      b.so_no                                                                           AS `so_no`,
      e.english_name                                                                    AS `client`,
      SUM(a.qty)                                                                        AS `qty`,
      SUM(a.qty_outstanding)                                                            AS `qty_outstanding`,
      b.discount                                                                        AS `discount`,
      b.currency_code                                                                   AS `currency`,
      SUM(a.qty_outstanding * a.price * (100 - b.discount) / 100)                       AS `amt_outstanding`,
      SUM(a.qty_outstanding * a.price * (100 - b.discount) / 100 * b.exchange_rate)     AS `amt_outstanding_base`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      `model` AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    LEFT JOIN
      `debtor` AS e
    ON b.debtor_code=e.code
    WHERE
      b.status=\"CONFIRMED\"
      $whereClause
    GROUP BY
      a.brand_code, b.so_date, b.so_no
    ORDER BY
      a.brand_code ASC,
      b.so_date ASC,
      b.so_no ASC
  ");

  $soModels = array();

  foreach ($results as $soModel) {
    $brandCode = $soModel["brand_code"];
    $brandName = $soModel["brand_name"];

    $arrayPointer = &$soModels;

    if (!isset($arrayPointer["$brandCode - $brandName"])) {
      $arrayPointer["$brandCode - $brandName"] = array();
    }
    $arrayPointer = &$arrayPointer["$brandCode - $brandName"];

    array_push($arrayPointer, $soModel);
  }

  $brands = query("
    SELECT DISTINCT
      a.brand_code  AS `code`,
      c.name        AS `name`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    WHERE
      b.status=\"CONFIRMED\"
    ORDER BY
      a.brand_code ASC
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
      <div class="headline"><?php echo SALES_REPORT_BRAND_DETAIL_TITLE; ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Brand:</th>
          </tr>
          <tr>
            <td>
              <select name="brand_code[]" multiple class="web-only">
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    $name = $brand["name"];
                    $selected = assigned($brandCodes) && in_array($code, $brandCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php echo assigned($brandCodes) ? join(", ", $brandCodes) : "ALL"; ?>
              </span>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
          <tr>
            <th>
              <input
                id="input-outstanding-only"
                class="web-only"
                type="checkbox"
                onchange="onOutstandingOnlyChanged(event)"
                <?php echo $showMode === "outstanding_only" ? "checked" : "" ?>
              />
              <label class="web-only" for="input-outstanding-only">Outstanding only</label>
              <input
                id="input-show-mode"
                type="hidden"
                name="show_mode"
                value="<?php echo $showMode; ?>"
              />
              <span class="print-only">
                <?php echo $showMode === "outstanding_only" ? "Outstanding only" : ""; ?>
              </span>
            </th>
          </tr>
        </table>
      </form>
      <?php if (count($soModels) > 0) : ?>
        <?php foreach ($soModels as $brand => &$models) : ?>
          <div class="so-model">
            <h4><?php echo $brand; ?></h4>
            <table class="so-results">
              <colgroup>
                <col style="width: 80px">
                <col>
                <col>
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 60px">
                <col style="width: 80px">
                <col style="width: 80px">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Date</th>
                  <th>Order No.</th>
                  <th>Client</th>
                  <th class="number">Qty</th>
                  <th class="number">Outstanding Qty</th>
                  <th class="number">Currency</th>
                  <th class="number">Outstanding Amt</th>
                  <th class="number"><?php echo $InBaseCurrency; ?></th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $totalQty = 0;
                  $totalOutstanding = 0;
                  $totalAmtBase = 0;

                  for ($i = 0; $i < count($models); $i++) {
                    $soModel = $models[$i];
                    $date = $soModel["date"];
                    $soId = $soModel["so_id"];
                    $soNo = $soModel["so_no"];
                    $client = $soModel["client"];
                    $qty = $soModel["qty"];
                    $outstandingQty = $soModel["qty_outstanding"];
                    $discount = $soModel["discount"];
                    $currency = $soModel["currency"];
                    $outstandingAmt = $soModel["amt_outstanding"];
                    $outstandingAmtBase = $soModel["amt_outstanding_base"];

                    $totalQty += $qty;
                    $totalOutstanding += $outstandingQty;
                    $totalAmtBase += $outstandingAmtBase;

                    echo "
                      <tr>
                        <td title=\"$date\">$date</td>
                        <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a></td>
                        <td title=\"$client\">$client</td>
                        <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                        <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                        <td title=\"$currency\" class=\"number\">$currency</td>
                        <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                        <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                      </tr>
                    ";
                  }
                ?>
                <tr>
                  <th></th>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($totalOutstanding); ?></th>
                  <th></th>
                  <th></th>
                  <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
                  <th></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="so-brand-no-results">No results</div>
      <?php endif ?>
    </div>
    <script>
      function onOutstandingOnlyChanged(event) {
        var showMode = event.target.checked ? "outstanding_only" : "show_all";
        document.querySelector("#input-show-mode").value = showMode;
        event.target.form.submit();
      }
    </script>
  </body>
</html>
