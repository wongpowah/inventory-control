<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "outstanding_only";

  $whereClause = "";

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $debtorCodes)) . ")";
  }

  if ($showMode == "outstanding_only") {
    $whereClause = $whereClause . "
      AND IFNULL(b.total_qty_outstanding, 0) > 0";
  }

  $soHeaders = query("
    SELECT
      a.debtor_code                                                                           AS `debtor_code`,
      CONCAT(a.debtor_code, \" - \", IFNULL(c.english_name, \"Unknown\"))                     AS `debtor`,
      COUNT(*)                                                                                AS `count`,
      SUM(IFNULL(b.total_qty, 0))                                                             AS `qty`,
      SUM(IFNULL(b.total_qty_outstanding, 0))                                                 AS `qty_outstanding`,
      SUM(IFNULL(b.total_amt_outstanding, 0) * (100 - a.discount) / 100 * a.exchange_rate)    AS `amt_outstanding_base`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                         AS `so_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`,
        SUM(qty_outstanding * price)  AS `total_amt_outstanding`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"CONFIRMED\"
      $whereClause
    GROUP BY
      a.debtor_code
    ORDER BY
      a.debtor_code ASC
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                           AS `code`,
      IFNULL(c.english_name, \"Unknown\")     AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                         AS `so_no`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"CONFIRMED\"
      " . ($showMode === "outstanding_only" ? "AND IFNULL(b.total_qty_outstanding, 0) > 0" : "") . "
    ORDER BY
      a.debtor_code ASC
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
      <div class="headline"><?php echo SALES_REPORT_CUSTOMER_SUMMARY_TITLE; ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($debtorCodes) && in_array($code, $debtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($debtorCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($debtors, function ($i) use ($debtorCodes) {
                    return in_array($i["code"], $debtorCodes);
                  }))) : "ALL";
                ?>
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
      <?php if (count($soHeaders) > 0) : ?>
        <table class="so-results">
          <colgroup>
            <col>
            <col style="width: 60px">
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Client</th>
              <th class="number"># Orders</th>
              <th class="number">Total Qty</th>
              <th class="number">Outstanding Qty</th>
              <th class="number">Outstanding Amt <?php echo $InBaseCurrency; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalCount = 0;
              $totalQty = 0;
              $totalOutstanding = 0;
              $totalAmtBase = 0;

              for ($i = 0; $i < count($soHeaders); $i++) {
                $soHeader = $soHeaders[$i];
                $debtorCode = $soHeader["debtor_code"];
                $debtor = $soHeader["debtor"];
                $count = $soHeader["count"];
                $qty = $soHeader["qty"];
                $outstandingQty = $soHeader["qty_outstanding"];
                $outstandingAmtBase = $soHeader["amt_outstanding_base"];

                $totalCount += $count;
                $totalQty += $qty;
                $totalOutstanding += $outstandingQty;
                $totalAmtBase += $outstandingAmtBase;

                echo "
                  <tr>
                    <td title=\"$debtor\">
                      <a class=\"link\" href=\"" . SALES_REPORT_CUSTOMER_DETAIL_URL . "?show_mode=$showMode&debtor_code[]=$debtorCode\">$debtor</a>
                    </td>
                    <td title=\"$count\" class=\"number\">" . number_format($count) . "</td>
                    <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                    <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                  </tr>
                ";
              }
            ?>
            <tr>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalCount); ?></th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($totalOutstanding); ?></th>
              <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
            </tr>
          </tbody>
        </table>
      </div>
    <?php else : ?>
      <div class="so-client-no-results">No results</div>
    <?php endif ?>
    <script>
      function onOutstandingOnlyChanged(event) {
        var showMode = event.target.checked ? "outstanding_only" : "show_all";
        document.querySelector("#input-show-mode").value = showMode;
        event.target.form.submit();
      }
    </script>
  </body>
</html>
