<?php
  define("SYSTEM_PATH", "../../../");

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
      <?php if (count($stockInHeaders) > 0) : ?>
        <?php foreach($stockInHeaders as &$stockInHeader) : ?>
          <?php $transCode = $stockInHeader["transaction_code"]; ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo STOCK_IN_PRINTOUT_TITLE ?></div>
            <table class="stock-in-header">
              <tr>
                <td>Voucher No.:</td>
                <td><?php echo $stockInHeader["stock_in_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $stockInHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>Transaction Code:</td>
                <td><?php echo $stockInHeader["transaction_type"]; ?></td>
                <td>Warehouse:</td>
                <td><?php echo $stockInHeader["warehouse"]; ?></td>
              </tr>
              <tr>
                <?php if ($transCode === "R1" || $transCode === "R3" || $transCode === "R7" || $transCode === "R8") : ?>
                  <td>Client:</td>
                  <td><?php echo $stockInHeader["creditor"]; ?></td>
                <?php endif ?>
                <?php if ($transCode === "R1") : ?>
                  <td>Currency:</td>
                  <td><?php echo $stockInHeader["currency"]; ?></td>
                <?php endif ?>
              </tr>
              <?php if ($transCode === "R1") : ?>
                <tr>
                  <td>Discount:</td>
                  <td><?php echo $stockInHeader["discount"]; ?>%</td>
                  <td>Net Amount:</td>
                  <td><?php echo $stockInHeader["net_amount"]; ?></td>
                </tr>
              <?php endif ?>
              <tr>
                <td>Status:</td>
                <td><?php echo $stockInHeader["status"]; ?></td>
              </tr>
            </table>
            <?php if (count($stockInModels[$stockInHeader["stock_in_no"]]) > 0) : ?>
              <?php $showPrice = $transCode === "R1" || $transCode === "R3"; ?>
              <table class="stock-in-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>Brand</th>
                    <th>Model No.</th>
                    <?php if ($showPrice) : ?><th class="number">Price</th><?php endif ?>
                    <th class="number">Qty</th>
                    <?php if ($showPrice) : ?><th class="number">Subtotal</th><?php endif ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $subtotalSum = 0;
                    $discount = $stockInHeader["discount"];
                    $tax = $stockInHeader["tax"];
                    $models = $stockInModels[$stockInHeader["stock_in_no"]];

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $price = $model["price"];
                      $qty = $model["qty"];
                      $subtotal = $model["subtotal"];

                      $totalQty += $qty;
                      $subtotalSum += $subtotal;

                      echo "
                        <tr>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          " . ($showPrice ? "<td class=\"number\">" . rtrim(rtrim($price, "0"), ".") . "</td>" : "") . "
                          <td class=\"number\">" . number_format($qty) . "</td>
                          " . ($showPrice ? "<td class=\"number\">" . number_format($subtotal, 2) . "</td>" : "") . "
                        </tr>
                      ";
                    }
                  ?>
                  <?php if ($discount > 0) : ?>
                    <tr>
                      <td></td>
                      <?php if ($showPrice) : ?><td></td><?php endif ?>
                      <td></td>
                      <th></th>
                      <?php if ($showPrice) : ?>
                        <th class="number"><?php echo number_format($subtotalSum, 2); ?></th>
                      <?php endif ?>
                    </tr>
                    <tr>
                      <td></td>
                      <?php if ($showPrice) : ?><td></td><?php endif ?>
                      <td></td>
                      <td class="number">Discount <?php echo $discount; ?>%</td>
                      <?php if ($showPrice) : ?>
                        <td class="number"><?php echo number_format($subtotalSum * $discount / 100, 2); ?></td>
                      <?php endif ?>
                    </tr>
                  <?php endif ?>
                  <tr>
                    <th></th>
                    <?php if ($showPrice) : ?><th></th><?php endif ?>
                    <th class="number">Total:</th>
                    <th class="number"><?php echo number_format($totalQty); ?></th>
                    <?php if ($showPrice) : ?>
                      <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
                    <?php endif ?>
                  </tr>
                  <?php if ($transCode === "R3") : ?>
                    <tr>
                      <th></th>
                      <?php if ($showPrice) : ?><th></th><?php endif ?>
                      <th class="number">Total Cost:</th>
                      <th></th>
                      <?php if ($showPrice) : ?>
                        <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / (100 + $tax), 2); ?></th>
                      <?php endif ?>
                    </tr>
                  <?php endif ?>
                </tbody>
              </table>
            <?php else : ?>
              <div class="stock-in-models-no-results">No models</div>
            <?php endif ?>
            <table class="stock-in-footer">
              <?php if (assigned($stockInHeader["remarks"])) : ?>
                <tr>
                  <td>Remarks:</td>
                  <td><?php echo $stockInHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="stock-in-not-found">Stock in voucher not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
