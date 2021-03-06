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
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_ALLOTMENT_STOCK_TITLE; ?></div>
      <form>
        <table id="stock-input">
          <tr>
            <th>Warehouse:</th>
            <th>Client:</th>
            <th>SO No.:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_warehouse_code[]" multiple class="web-only">
                <?php
                  foreach ($warehouses as $warehouse) {
                    $code = $warehouse["code"];
                    $name = $warehouse["name"];
                    $selected = assigned($filterWarehouseCodes) && in_array($code, $filterWarehouseCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($filterWarehouseCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($warehouses, function ($i) use ($filterWarehouseCodes) {
                    return in_array($i["code"], $filterWarehouseCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
            <td>
              <select name="filter_debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($filterDebtorCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($debtors, function ($i) use ($filterDebtorCodes) {
                    return in_array($i["code"], $filterDebtorCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
            <td>
              <select name="filter_so_no[]" multiple class="web-only">
                <?php
                  foreach ($soNos as $soNo) {
                    $no = $soNo["so_no"];
                    $selected = assigned($filterSONos) && in_array($no, $filterSONos) ? "selected" : "";
                    echo "<option value=\"$no\" $selected>$no</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo assigned($filterSONos) ? join(", ", $filterSONos) : "ALL"; ?></span>
            </td>
            <td><button type="submit" class="web-only">Go</button></td>
          </tr>
        </table>
        <div class="time-generation print-only">Time of generation: <?php echo date("H:i:s d-m-Y"); ?></div>
      </form>
      <?php if (count($stockResults) > 0) : ?>
        <form method="post" class="stock-form">
          <button type="submit" class="web-only">Save</button>
          <?php
            foreach ($stockResults as $warehouseCode => $warehouse) {
              $warehouseName = $warehouse["name"];
              $models = $warehouse["models"];

              echo "
                <div class=\"warehouse\">
                  <h4>$warehouseCode - $warehouseName</h4>
                  <table class=\"stock-header\">
                    <tr class=\"web-only\">
                      <td>Reserve: <input data-warehouse_code=\"$warehouseCode\" type=\"number\" class=\"reserve-percentage\" min=\"0\" max=\"100\" value=\"0\"/><span>%</span></td>
                    </tr>
                    <tr class=\"web-only\">
                      <td>
                        <button type=\"button\" class=\"header-button\" onclick=\"allocateByPriorities('$warehouseCode')\">Allocate by priorities</button>
                        <button type=\"button\" class=\"header-button\" onclick=\"allocateBySoDate('$warehouseCode')\">Allocate by date</button>
                        <button type=\"button\" class=\"header-button\" onclick=\"allocateBySoProportion('$warehouseCode')\">Allocate by proportion</button>
                        <button type=\"button\" class=\"header-button\" onclick=\"resetAllotments('$warehouseCode')\">Reset</button>
                      </td>
                    </tr>
                  </table>
              ";

              if (count($models) > 0) {
                echo "
                  <table class=\"stock-results\" data-warehouse_code=\"$warehouseCode\">
                    <colgroup>
                      <col style=\"width: 70px\">
                      <col>
                      <col style=\"width: 75px\">
                      <col>
                      <col style=\"width: 90px\">
                      <col style=\"width: 80px\">
                      <col style=\"width: 75px\">
                      <col style=\"width: 75px\">
                      <col style=\"width: 75px\">
                    </colgroup>
                    <thead>
                      <tr></tr>
                      <tr>
                        <th>Brand</th>
                        <th>Model No.</th>
                        <th class=\"number\">Allottable Qty</th>
                        <th>SO No.</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th class=\"number\">Outstanding Qty</th>
                        <th class=\"number\">Allot Qty</th>
                        <th class=\"number\">Total Allotted Qty</th>
                      </tr>
                    </thead>
                    <tbody>
                ";

                $totalQty = 0;

                for ($i = 0; $i < count($models); $i++) {
                  $model = $models[$i];
                  $brandCode = $model["brand_code"];
                  $brandName = $model["brand_name"];
                  $modelNo = $model["model_no"];
                  $qty = $model["qty_available"];
                  $totalQty += $qty;

                  $soNoColumn = "";
                  $debtorNameColumn = "";
                  $dateColumn = "";
                  $outstandingColumn = "";
                  $allotColumn = "";

                  $matchedModels = $soModels[$brandCode][$modelNo];

                  if (isset($matchedModels)) {
                    foreach ($matchedModels as $matchedModel) {
                      $soId = $matchedModel["so_id"];
                      $soNo = $matchedModel["so_no"];
                      $priority = $matchedModel["priority"];
                      $debtorCode = $matchedModel["debtor_code"];
                      $debtorName = $matchedModel["debtor_name"];
                      $date = $matchedModel["date"];

                      $soNoColumn = $soNoColumn . "
                        <div class=\"cell\" title=\"$soNo\" data-so_no=\"$soNo\"><a href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a></div>
                      ";
                      $debtorNameColumn = $debtorNameColumn . "
                        <div class=\"cell\" title=\"$debtorName\" data-so_no=\"$soNo\">$debtorName</div>
                      ";
                      $dateColumn = $dateColumn . "
                        <div class=\"cell\" title=\"$date\" data-so_no=\"$soNo\">$date</div>
                      ";
                      $outstandingColumn = $outstandingColumn . "
                        <div class=\"cell outstanding-qty number\" data-so_no=\"$soNo\">0</div>
                      ";
                      $allotColumn = $allotColumn . "
                      <div class=\"cell number\" data-so_no=\"$soNo\">
                        <input type=\"hidden\" name=\"warehouse_code[]\" value=\"$warehouseCode\" />
                        <input type=\"hidden\" name=\"so_no[]\" value=\"$soNo\" />
                        <input type=\"hidden\" name=\"brand_code[]\" value=\"$brandCode\" />
                        <input type=\"hidden\" name=\"model_no[]\" value=\"$modelNo\" />
                        <input
                          type=\"number\"
                          name=\"qty[]\"
                          value=\"0\"
                          min=\"0\"
                          max=\"0\"
                          data-so_no=\"$soNo\"
                          data-priority=\"$priority\"
                          data-date=\"$date\"
                          class=\"allot-qty number\"
                          onchange=\"onQtyChange(event, '$warehouseCode', '$brandCode', '$modelNo', '$soNo')\"
                          required
                        />
                      </div>
                      ";
                    }
                  }

                  echo "
                    <tr
                      class=\"stock-model\"
                      data-brand_code=\"$brandCode\"
                      data-model_no=\"$modelNo\"
                      data-priority=\"$priority\"
                      data-date=\"$date\"
                    >
                      <td title=\"$brandName\">$brandName</td>
                      <td title=\"$modelNo\">$modelNo</td>
                      <td class=\"number\">$qty</td>
                      <td>$soNoColumn</td>
                      <td>$debtorNameColumn</td>
                      <td>$dateColumn</td>
                      <td>$outstandingColumn</td>
                      <td>$allotColumn</td>
                      <td class=\"total-model-allot-qty number\">0</td>
                    </tr>
                  ";
                }

                echo "
                      <tr>
                        <th></th>
                        <th class=\"number\">Total:</th>
                        <th class=\"number total-qty\">$totalQty</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class=\"total-allot-qty number\"></th>
                      </tr>
                      <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class=\"total-allot-qty-p number\"></th>
                      </tr>
                    </tbody>
                  </table>
                ";
              } else {
                echo "<div class=\"stock-no-results\">No incoming advice models</div>";
              }

              echo "</div>";
            }
          ?>
        </form>
      <?php else : ?>
        <div class="stock-no-results">No results</div>
      <?php endif ?>
    </div>
    <script>
      var stockModels = <?php echo json_encode($stockModels); ?>;
      var soModels = <?php echo json_encode($soModels); ?>;
      var allotments = <?php echo json_encode($allotments); ?>;

      function getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var otherWarehouseCodes = Object.keys(allotments).filter(function (i) { return i !== warehouseCode; });

        for (var i = 0; i < otherWarehouseCodes.length; i++) {
          var code = otherWarehouseCodes[i];
          var allotment =
            allotments[code] &&
            allotments[code][brandCode] &&
            allotments[code][brandCode][modelNo] &&
            allotments[code][brandCode][modelNo][soNo];

          if (allotment && allotment["qty"]) {
            totalQty += parseFloat(allotment["qty"]);
          }
        }

        return totalQty;
      }

      function getOtherSoAllottedQty(warehouseCode, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var modelAllotments =
          allotments[warehouseCode] &&
          allotments[warehouseCode][brandCode] &&
          allotments[warehouseCode][brandCode][modelNo] &&
          allotments[warehouseCode][brandCode][modelNo];
        var otherSoNos = Object.keys(modelAllotments).filter(function (i) { return i !== soNo; });

        for (var i = 0; i < otherSoNos.length; i++) {
          var code = otherSoNos[i];
          var allotment =
          allotments[warehouseCode] &&
          allotments[warehouseCode][brandCode] &&
          allotments[warehouseCode][brandCode][modelNo] &&
          allotments[warehouseCode][brandCode][modelNo][code];

          if (allotment && allotment["qty"]) {
            totalQty += parseFloat(allotment["qty"]);
          }
        }

        return totalQty;
      }

      function render() {
        var stockElements = document.querySelectorAll(".stock-results");

        for (var i = 0; i < stockElements.length; i++) {
          var stockElement = stockElements[i];
          var warehouseCode = stockElement.dataset.warehouse_code;

          renderIaTable(warehouseCode);
        }
      }

      function renderIaTable(warehouseCode) {
        var stockModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");

        for (var j = 0; j < stockModelElements.length; j++) {
          var stockModelElement = stockModelElements[j];
          var brandCode = stockModelElement.dataset.brand_code;
          var modelNo = stockModelElement.dataset.model_no;
          var allotQtyElements = stockModelElement.querySelectorAll(".allot-qty");

          for (var i = 0; i < allotQtyElements.length; i++) {
            var allotQtyElement = allotQtyElements[i];
            var soNo = allotQtyElement.dataset.so_no;

            allotments[warehouseCode] = allotments[warehouseCode] || {};
            allotments[warehouseCode][brandCode] = allotments[warehouseCode][brandCode] || {};
            allotments[warehouseCode][brandCode][modelNo] = allotments[warehouseCode][brandCode][modelNo] || {};
            allotments[warehouseCode][brandCode][modelNo][soNo] = allotments[warehouseCode][brandCode][modelNo][soNo] || {};

            var allotment = allotments[warehouseCode][brandCode][modelNo][soNo];
            allotment["do_no"] = allotment["do_no"] || "";
            allotment["qty"] = allotment["qty"] || 0;

            renderAllotment(warehouseCode, brandCode, modelNo, soNo);
          }
        }

        renderWarehouseAllotmentSum(warehouseCode);
      }

      function renderAllotment(warehouseCode, brandCode, modelNo, soNo) {
        if (
          allotments[warehouseCode] &&
          allotments[warehouseCode][brandCode] &&
          allotments[warehouseCode][brandCode][modelNo] &&
          allotments[warehouseCode][brandCode][modelNo][soNo]
        ) {
          var reservePercentage = document.querySelector("input.reserve-percentage[data-warehouse_code=\"" + warehouseCode + "\"]").value || 0;
          var warehouseSelector = ".stock-results[data-warehouse_code=\"" + warehouseCode + "\"]";
          var stockModelSelector = warehouseSelector + " .stock-model[data-brand_code=\"" + brandCode + "\"][data-model_no=\"" + modelNo + "\"]";
          var allotmentElements = document.querySelectorAll(stockModelSelector + " .cell[data-so_no=\"" + soNo + "\"]");
          var outstandingQtyElement = document.querySelector(stockModelSelector + " .outstanding-qty[data-so_no=\"" + soNo + "\"]");
          var allotQtyElement = document.querySelector(stockModelSelector + " .allot-qty[data-so_no=\"" + soNo + "\"]");
          var allotment = allotments[warehouseCode][brandCode][modelNo][soNo];
          var allotQty = parseFloat(allotment["qty"]);
          var doNo = allotment["do_no"] ? allotment["do_no"] : "";
          var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
          var availableQty = Math.floor(parseFloat(stockModels[warehouseCode][brandCode][modelNo]["qty"]) * (1 - reservePercentage / 100));
          var otherAllotedWarehouseQty = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo);
          var otherAllotedSoQty = getOtherSoAllottedQty(warehouseCode, brandCode, modelNo, soNo);
          var allottableIaQty = availableQty - otherAllotedSoQty;
          var allottableSoQty = outstandingQty - otherAllotedWarehouseQty;
          var maxQty = Math.min(allottableIaQty, allottableSoQty);
          allotQty = Math.min(maxQty, allotQty);

          outstandingQtyElement.innerHTML = allottableSoQty;

          for (var i = 0; i < allotmentElements.length; i++) {
            toggleClass(allotmentElements[i], "hide", allottableSoQty === 0);
          }

          allotQtyElement.max = maxQty;
          allotQtyElement.value = allotQty;

          if (doNo !== "") {
            allotQtyElement.setAttribute("readonly", true);
            allotQtyElement.title = doNo;
          } else {
            allotQtyElement.removeAttribute("readonly");
          }

          toggleClass(allotQtyElement, "packed", doNo !== "");

          allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = allotQty;

          var totalModelAllotQtyElement = document.querySelector(stockModelSelector + " .total-model-allot-qty");
          var allotQtyElements = document.querySelectorAll(stockModelSelector + " .allot-qty");
          var totalModelAllotQty = 0;

          for (var i = 0; i < allotQtyElements.length; i++) {
            totalModelAllotQty += parseFloat(allotQtyElements[i].value);
          }

          totalModelAllotQtyElement.innerHTML = totalModelAllotQty;
        }
      }

      function renderWarehouseAllotmentSum(warehouseCode) {
        var warehouseSelector = ".stock-results[data-warehouse_code=\"" + warehouseCode + "\"]";
        var totalQtyElement = document.querySelector(warehouseSelector + " .total-qty");
        var totalAllotQtyElement = document.querySelector(warehouseSelector + " .total-allot-qty");
        var totalAllotQtyPElement = document.querySelector(warehouseSelector + " .total-allot-qty-p");
        var totalModelAllotQtyElements = document.querySelectorAll(warehouseSelector + " .total-model-allot-qty");
        var totalQty = totalQtyElement.innerHTML;
        var totalAllotQty = 0;

        for (var i = 0; i < totalModelAllotQtyElements.length; i++) {
          totalAllotQty += parseFloat(totalModelAllotQtyElements[i].innerHTML);
        }

        totalAllotQtyElement.innerHTML = totalAllotQty;
        totalAllotQtyPElement.innerHTML = "(" + (totalAllotQty / totalQty * 100).toFixed(2) + "%)";
      }

      function onQtyChange(event, warehouseCode, brandCode, modelNo, soNo) {
        allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = event.target.value;

        var soNos = Object.keys(soModels[brandCode][modelNo]);
        var otherSoNos = Object.keys(soModels[brandCode][modelNo]).filter(function (i) { return i !== soNo; });
        var otherIANos = Object.keys(stockModels).filter(function (i) { return i !== warehouseCode; });

        renderAllotment(warehouseCode, brandCode, modelNo, soNo);

        for (var i = 0; i < otherSoNos.length; i++) {
          renderAllotment(warehouseCode, brandCode, modelNo, otherSoNos[i]);
        }

        renderWarehouseAllotmentSum(warehouseCode);

        for (var i = 0; i < otherIANos.length; i++) {
          renderAllotment(otherIANos[i], brandCode, modelNo, soNo);
        }
      }

      function allocateModels(warehouseCode, elements, sorting) {
        for (var i = 0; i < elements.length; i++) {
          var element = elements[i];
          var brandCode = element.dataset.brand_code;
          var modelNo = element.dataset.model_no;
          var allotQtyElements = element.querySelectorAll(".allot-qty");

          var allotQtyElementList = [];

          for (var j = 0; j < allotQtyElements.length; j++) {
            allotQtyElementList.push(allotQtyElements[j]);
          }

          allotQtyElementList.sort(sorting);

          for (var k = 0; k < allotQtyElementList.length; k++) {
            var allotQtyElement = allotQtyElementList[k];

            var soNo = allotQtyElement.dataset.so_no;
            var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
            var otherAllotedWarehouseQty = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo);
            var allottableSoQty = outstandingQty - otherAllotedWarehouseQty;

            allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = allottableSoQty;

            renderAllotment(warehouseCode, brandCode, modelNo, soNo);
          }
        }
      }

      function allocateByPriorities(warehouseCode) {
        resetAllotments(warehouseCode);

        var warehouseModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");

        allocateModels(warehouseCode, warehouseModelElements, function (a, b) {
          return b.dataset.priority - a.dataset.priority;
        });

        render();
      }

      function allocateBySoDate(warehouseCode) {
        resetAllotments(warehouseCode);

        var warehouseModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");

        allocateModels(warehouseCode, warehouseModelElements, function (a, b) {
          return getTime(a.dataset.date) - getTime(b.dataset.date);
        });

        render();
      }

      function allocateBySoProportion(warehouseCode) {
        resetAllotments(warehouseCode);

        var warehouseModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");
        var reservePercentage = document.querySelector("input.reserve-percentage[data-warehouse_code=\"" + warehouseCode + "\"]").value || 0;

        for (var i = 0; i < warehouseModelElements.length; i++) {
          var warehouseModelElement = warehouseModelElements[i];
          var brandCode = warehouseModelElement.dataset.brand_code;
          var modelNo = warehouseModelElement.dataset.model_no;
          var availableQty = stockModels[warehouseCode][brandCode][modelNo]["qty"] * (1 - reservePercentage / 100);
          var allotQtyElements = warehouseModelElement.querySelectorAll(".allot-qty");

          for (var j = 0; j < allotQtyElements.length; j++) {
            var allotQtyElement = allotQtyElements[j];

            var soNo = allotQtyElement.dataset.so_no;

            var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
            var otherAllotedWarehouseQty = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo);
            var allottableSoQty = outstandingQty - otherAllotedWarehouseQty;
            var totalModelAllottableQty = 0;
            var soNos = Object.keys(soModels[brandCode][modelNo]);

            for (var k = 0; k < soNos.length; k++) {
              var outstandingQty2 = parseFloat(soModels[brandCode][modelNo][soNos[k]]["qty_outstanding"]);
              var otherAllotedWarehouseQty2 = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNos[k]);
              var allottableSoQty2 = outstandingQty2 - otherAllotedWarehouseQty2;

              totalModelAllottableQty += allottableSoQty2;
            }

            var proportion = allottableSoQty / totalModelAllottableQty;

            var round = j % 2 === 1 ? Math.floor : Math.ceil;
            allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = round(proportion * availableQty);

            renderAllotment(warehouseCode, brandCode, modelNo, soNo);
          }
        }

        render();
      }

      function resetAllotments(warehouseCode) {
        var stockModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");

        for (var i = 0; i < stockModelElements.length; i++) {
          var stockModelElement = stockModelElements[i];
          var brandCode = stockModelElement.dataset.brand_code;
          var modelNo = stockModelElement.dataset.model_no;
          var allotQtyElements = stockModelElement.querySelectorAll(".allot-qty");

          for (var j = 0; j < allotQtyElements.length; j++) {
            var allotQtyElement = allotQtyElements[j];

            var soNo = allotQtyElement.dataset.so_no;
            var allotment = allotments[warehouseCode][brandCode][modelNo][soNo];

            if (allotment["do_no"] === "") {
              allotment["qty"] = 0;
            }
          }
        }

        render();
      }

      window.addEventListener("load", function () {
        var stockForms = document.querySelectorAll(".stock-form");

        for (var i = 0; i < stockForms.length; i++) {
          stockForms[i].reset();
        }

        render();
      });
    </script>
  </body>
</html>
