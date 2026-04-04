<?php
// api/shipment/export_result.php
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Bulk_Upload_Result_' . time() . '.xls"');

require_once '../../config/db.php';

$jobId = $_GET['id'] ?? null;
if (!$jobId) {
    echo "No Job ID provided";
    exit;
}

$stmt = $pdo->prepare("SELECT result_file, filename FROM tbl_bulkupload_jobs WHERE id = :id");
$stmt->execute([':id' => $jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job || empty($job['result_file'])) {
    echo "Result data not found for this Job ID";
    exit;
}

$resultData = json_decode($job['result_file'], true);
if (!$resultData) {
    echo "Invalid result data format";
    exit;
}

// Reuse the HTML generation logic
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns="http://www.w3.org/TR/REC-html40">

<head>
    <style>
        .required {
            background-color: #ffcccc;
            font-weight: bold;
        }

        .optional {
            background-color: #e0e0e0;
            font-weight: bold;
        }

        td {
            mso-number-format: "\@";
        }
    </style>
</head>

<body>
    <table border="1">
        <?php
        // Indices definitions (must match template)
        $reqCols = [0, 1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 15, 16, 17, 18, 19, 22, 23, 24, 25, 26, 30];
        $optCols = [3, 13, 14, 20, 21, 27, 28, 29];

        foreach ($resultData as $i => $row) {
            $isHeader = ($i === 0);

            // For JSON, we store the full array. 
            // The structure we'll use in bulk_upload: [col0, col1, ..., waybill, status, remarks, errCol]
        
            $errColIdx = -1;
            if (!$isHeader) {
                $errColIdx = array_pop($row);
            }

            $remarksColIdx = count($row) - 1;
            $statusColIdx = count($row) - 2;
            $waybillColIdx = count($row) - 3;
            $statusVal = $row[$statusColIdx] ?? '';

            echo "<tr>";
            foreach ($row as $k => $cell) {
                $style = '';
                $class = '';
                if ($isHeader) {
                    if (in_array($k, $reqCols))
                        $class = 'class="required"';
                    elseif (in_array($k, $optCols))
                        $class = 'class="optional"';
                    else
                        $style = 'style="font-weight:bold; background-color:#f0f0f0;"';
                } else {
                    if ($k == $statusColIdx) {
                        if ($cell === 'Failed')
                            $style = 'style="background-color:#ffcccc;color:#cc0000;font-weight:bold;"';
                        else if ($cell === 'Success')
                            $style = 'style="background-color:#ccffcc;color:#006600;font-weight:bold;"';
                    } elseif ($k == $remarksColIdx && $statusVal === 'Failed') {
                        $style = 'style="background-color:#ffccdd;color:#cc0000;"';
                    } elseif ($k == $errColIdx && $statusVal === 'Failed') {
                        $style = 'style="background-color:#ff0000;color:#ffffff;font-weight:bold;"';
                    }
                }
                echo "<td $class $style>" . htmlspecialchars($cell ?? '') . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
</body>

</html>