<?php
    use PhpOffice\PhpSpreadsheet\Helper\Sample;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    
    class ExcelController {
        private $spreadsheet;
        public function __construct() {

        }
        public function loadFile($inputFileName) {
            $this->spreadsheet = IOFactory::load($inputFileName);
            return true;
        }
        public function loadSpreadSheet($pageId) {
            $dataSheet = $this->spreadsheet->getSheet($pageId);
            $highestRow = $dataSheet->getHighestRow();
            $dataSheet = $dataSheet->toArray(null, true, true, true);
            return $dataSheet;
        }
        public function reportFolders($data) {
            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            // Set document properties
            $spreadsheet->getProperties()->setTitle('Sheet1')->setCreator('Compliance Hub');
            // Define the headers and the corresponding data keys
            $headers = [
                'Clave' => 'key_folder',
                'Cliente' => 'name_folder',
                'Inicio del plazo' => 'first_fech_folder',
                'Fin del plazo' => 'second_fech_folder',
                'Estatus' => 'status',
                'Asesor' => 'name_customer',
                'Autor' => 'name_user',
                'Vo.Bo. Alta Facturación' => 'chk_alta_fact_folder',
                'Vo.Bo. Liberación' => 'chk_lib_folder',
                'Original Recibido' => 'chk_orig_recib_folder',
                'Fecha de original recibido' => 'fech_orig_recib_folder',
                'Fecha de registro' => 'created_at_folder'
            ];
            // Set headers
            $spreadsheet->setActiveSheetIndex(0)->fromArray(array_keys($headers), NULL, 'A1');
            $spreadsheet->getActiveSheet()->getStyle('A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . '1')->getFont()->setBold(true);
            // Prepare data
            $dataArray = [];
            foreach ($data as $row) {
                // Calculate status based on 'dias'
                if ($row['dias'] === null) {
                    $status = 'Sin plazo de vencimiento';
                    $color = 'BFE6F5'; // Blue
                } elseif ($row['dias'] >= 1) {
                    $status = 'Cliente vencido';
                    $color = 'FEA793'; // Red
                } elseif ($row['dias'] >= -60) {
                    $status = 'Cerca de vencimiento';
                    $color = 'FFFC9B'; // Orange
                } else {
                    $status = 'Cliente vigente';
                    $color = 'C0F0C8'; // Green
                }
                $txtAltaFactFolder = ($row['chk_alta_fact_folder'] === "Si") ? 'Si' : '- - -';
                $txtChkLibFolder = ($row['chk_lib_folder'] === "Si") ? 'Si' : '- - -';
                $txtChkOrigRecibFolder = ($row['chk_orig_recib_folder'] === "Si") ? 'Si' : '- - -';
                $fechOrigRecibFolder = ($row['chk_orig_recib_folder'] === "Si") ? $row['fech_orig_recib_folder'] : '- - -';
                
                $dataArray[] = [
                    $row['key_folder'],
                    $row['name_folder'],
                    $row['first_fech_folder'],
                    $row['second_fech_folder'],
                    $status,
                    $row['name_customer'],
                    $row['name_user'],
                    $txtAltaFactFolder,
                    $txtChkLibFolder,
                    $txtChkOrigRecibFolder,
                    $fechOrigRecibFolder,
                    $row['created_at_folder']
                ];
                // Store colors for later use
                $colors[] = $color;
            }
            // Add data below the headers
            $spreadsheet->getActiveSheet(0)->fromArray(
                $dataArray,  // The data to set
                NULL,        // Array values with this value will not be set
                'A2'         // Top left coordinate of the worksheet range where we want to set these values (default is A1)
            );
            // Apply styles to status column
            foreach ($colors as $index => $color) {
                $cell = 'E' . ($index + 2); // Column G, starting from row 2
                $spreadsheet->getActiveSheet()->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($color);
            }
            
            // Adjust column widths as necessary
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(25);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(35);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(25);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(25);
            $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(25);
            // Alinear todas las celdas a la izquierda
            $spreadsheet->getActiveSheet()
                ->getStyle('A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow())
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                
            // Seleccionar la celda A1 para que no aparezca todo seleccionado
            $spreadsheet->getActiveSheet()->setSelectedCell('A1');
            
            // Rename worksheet
            $spreadsheet->getActiveSheet()->setTitle('Reporte de clientes');
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $spreadsheet->setActiveSheetIndex(0);
            // Obtener la fecha y hora actual en el formato que necesitas
            $timestamp = date('d-m-Y-h-i-a');
            // Concatenar el timestamp con el nombre base del archivo
            $filename = "Reporte-de-clientes-{$timestamp}.xlsx";
            
            // Redirect output to a client’s web browser (Xlsx)
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            // header('Content-Disposition: attachment;filename="Reporte-de-Clientes.xlsx"');
            header("Content-Disposition: attachment;filename=\"{$filename}\"");
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;
        }
    }
?>
