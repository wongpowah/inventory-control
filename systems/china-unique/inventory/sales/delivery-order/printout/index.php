<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "process.php";
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
      <?php if (count($doHeaders) > 0) : ?>
        <?php foreach($doHeaders as &$doHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <table class="do-header">
              <tr>
                <td>編號:</td>
                <td><?php echo $doHeader["do_no"]; ?></td>
              </tr>
              <tr>
                <td>致:</td>
                <td><?php echo $doHeader["debtor_name"] . " (" . $doHeader["debtor_code"] . ")"; ?></td>
              </tr>
              <tr>
                <td>地址:</td>
                <td><?php echo $doHeader["address"]; ?></td>
              </tr>
              <tr>
                <td>收貨人:</td>
                <td><?php echo $doHeader["contact"]; ?></td>
              </tr>
              <tr>
                <td>電話:</td>
                <td><?php echo $doHeader["tel"]; ?></td>
              </tr>
              <tr>
                <td>日期:</td>
                <td><?php echo $doHeader["date"]; ?></td>
              </tr>
            </table>
            <div class="headline"><?php echo SALES_DELIVERY_ORDER_PRINTOUT_TITLE . " (" . $doHeader["warehouse"] . "發貨)" ?></div>
            <?php if (count($doModels[$doHeader["do_no"]]) > 0) : ?>
              <table class="do-models sortable">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>訂單編號</th>
                    <th>品牌</th>
                    <th>型號</th>
                    <th class="number">數量</th>
                    <?php if (!$hidePrice) : ?>
                      <th class="number">含稅單價<br/>(<?php echo $doHeader["tax"]; ?>%)</th>
                      <th class="number">含稅總金額<br/>(<?php echo $doHeader["tax"]; ?>%)</th>
                    <?php endif ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $totalAmount = 0;
                    $discount = $doHeader["discount"];
                    $models = $doModels[$doHeader["do_no"]];

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $soNo = $model["so_no"];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $qty = $model["qty"];
                      $price = $model["price"];
                      $subtotal = $price * $qty;

                      $totalQty += $qty;
                      $totalAmount += $subtotal;

                      echo "
                        <tr>
                          <td>$soNo</td>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          <td class=\"number\">" . number_format($qty) . "</td>
                      ";

                      if (!$hidePrice) {
                        echo "
                          <td class=\"number\">" . rtrim(rtrim($price, "0"), ".") . "</td>
                          <td class=\"number\">" . number_format($subtotal, 2) . "</td>
                        ";
                      }

                      echo "</tr>";
                    }
                  ?>
                </tbody>
                <tbody>
                  <?php if ($discount > 0) : ?>
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <?php if (!$hidePrice) : ?>
                        <th></th>
                        <th class="number"><?php echo number_format($totalAmount, 2); ?></th>
                      <?php endif ?>
                    </tr>
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <?php if (!$hidePrice) : ?>
                        <td class="number">折扣: <?php echo $discount; ?>%</td>
                        <td class="number"><?php echo number_format($totalAmount * $discount / 100, 2); ?></td>
                      <?php endif ?>
                    </tr>
                  <?php endif ?>
                  <tr>
                    <th></th>
                    <th></th>
                    <th class="number">總數量:</th>
                    <th class="number"><?php echo number_format($totalQty); ?></th>
                    <?php if (!$hidePrice) : ?>
                      <th class="number">總金額:</th>
                      <th class="number"><?php echo number_format($totalAmount * (100 - $discount) / 100, 2); ?></th>
                    <?php endif ?>
                  </tr>
                </tbody>
              </table>
            <?php else : ?>
              <div class="do-models-no-results">沒有項目</div>
            <?php endif ?>
            <table class="do-footer">
              <?php if (assigned($doHeader["remarks"])) : ?>
                <tr>
                  <td>備註:</td>
                  <td><?php echo $doHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
              <tr><td colspan="2">敬請簽收:</td></tr>
              <tr><td colspan="2"><br/><br/><br/><br/>____________________________________</td></tr>
              <tr><td colspan="2"><?php echo $doHeader["debtor_name"]; ?></td></tr>
            </table>
          </div>
        <?php endforeach; ?>
        <div class="web-only printout-button-wrapper">
        <?php
          if (isSupervisor()) {
            echo generateRedirectButton(SALES_DELIVERY_ORDER_INTERNAL_PRINTOUT_URL, "內部印本");
          }

          if (!$hidePrice) {
            $_GET["show_price"] = "off";
            echo generateRedirectButton(CURRENT_URL, "隱藏價格");
          } else {
            $_GET["show_price"] = "on";
            echo generateRedirectButton(CURRENT_URL, "顯示價格");
          }

          unset($_GET["show_price"]);

          if (assigned($editId)) {
            $_POST["editing"] = "on";
            echo generateRedirectButton(SALES_DELIVERY_ORDER_URL . "?id=$editId", "編輯");
          }
        ?>
      <?php else : ?>
        <div id="do-not-found">找不到結果</div>
      <?php endif ?>
    </div>
  </body>
</html>
