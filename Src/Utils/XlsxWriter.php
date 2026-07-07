<?php

declare(strict_types=1);

/**
 * AttendQR — XlsxWriter
 * Genera archivos .xlsx (Office Open XML) en memoria pura, sin ext-zip.
 * El ZIP se construye con pack() siguiendo la especificación PKZip 2.0.
 *
 * Compatible con: Microsoft Excel, LibreOffice Calc, Google Sheets.
 * Requisitos PHP: solo extensiones estándar (ninguna adicional).
 */
class XlsxWriter
{
    // ── Constantes de estilo ─────────────────────────────────────────────────
    const S_DEFAULT   = 0;
    const S_HDR_GREEN = 1;
    const S_HDR_GREY  = 2;
    const S_HDR_BLUE  = 3;
    const S_HDR_PROG  = 4;
    const S_COL_NAME  = 5;
    const S_COL_DATE  = 6;
    const S_CELL_A    = 7;
    const S_CELL_F    = 8;
    const S_CELL_SESS = 9;
    const S_NAME_IDX  = 10;
    const S_NAME_TXT  = 11;
    const S_TOTAL_F   = 12;
    const S_TOTAL_A   = 13;
    const S_BLANK     = 14;

    private array $sheets  = [];
    private array $ssIndex = [];
    private array $ssArr   = [];

    // ── API pública ──────────────────────────────────────────────────────────

    public function addSheet(string $name): int
    {
        $idx = count($this->sheets);
        $this->sheets[$idx] = [
            'name'       => $name,
            'cells'      => [],
            'merges'     => [],
            'colWidths'  => [],
            'rowHeights' => [],
        ];
        return $idx;
    }

    public function cell(int $sheet, string $colStr, int $row, mixed $value, int $style = self::S_DEFAULT): void
    {
        $col  = $this->colIndex($colStr);
        $type = '';
        $v    = $value;

        if (is_string($value) && $value !== '') {
            $type = 's';
            $v    = $this->addSS($value);
        } elseif (is_int($value) || is_float($value)) {
            $type = 'n';
        }

        $this->sheets[$sheet]['cells'][$row][$col] = ['v' => $v, 's' => $style, 't' => $type];
    }

    public function merge(int $sheet, string $from, string $to): void
    {
        $this->sheets[$sheet]['merges'][] = "$from:$to";
    }

    public function colWidth(int $sheet, string $colStr, float $width): void
    {
        $this->sheets[$sheet]['colWidths'][$this->colIndex($colStr)] = $width;
    }

    public function rowHeight(int $sheet, int $row, float $height): void
    {
        $this->sheets[$sheet]['rowHeights'][$row] = $height;
    }

    public function colLetter(int $idx): string
    {
        $n = $idx + 1;
        $l = '';
        while ($n > 0) {
            $n--;
            $l = chr(65 + ($n % 26)) . $l;
            $n = intdiv($n, 26);
        }
        return $l;
    }

    /** Genera y devuelve el binario .xlsx listo para enviar al navegador. */
    public function output(): string
    {
        $zip = new XlsxZipBuilder();
        $zip->add('[Content_Types].xml',        $this->buildContentTypes());
        $zip->add('_rels/.rels',                $this->buildRels());
        $zip->add('xl/workbook.xml',            $this->buildWorkbook());
        $zip->add('xl/_rels/workbook.xml.rels', $this->buildWorkbookRels());
        $zip->add('xl/styles.xml',              $this->buildStyles());
        $zip->add('xl/sharedStrings.xml',       $this->buildSharedStrings());

        foreach (array_keys($this->sheets) as $i) {
            $zip->add("xl/worksheets/sheet{$i}.xml", $this->buildSheet($i));
        }

        return $zip->build();
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    private function addSS(string $str): int
    {
        if (!isset($this->ssIndex[$str])) {
            $this->ssIndex[$str] = count($this->ssArr);
            $this->ssArr[]       = $str;
        }
        return $this->ssIndex[$str];
    }

    private function colIndex(string $col): int
    {
        $col = strtoupper(trim($col));
        $n   = 0;
        foreach (str_split($col) as $c) {
            $n = $n * 26 + (ord($c) - 64);
        }
        return $n - 1;
    }

    private function cellRef(int $colIdx, int $row): string
    {
        return $this->colLetter($colIdx) . $row;
    }

    private function xmlEsc(string $str): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $str);
    }

    // ── Constructores XML ────────────────────────────────────────────────────

    private function buildContentTypes(): string
    {
        $sheets = '';
        foreach (array_keys($this->sheets) as $i) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml"'
                     . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
             . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
             . '<Default Extension="xml" ContentType="application/xml"/>'
             . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
             . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
             . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
             . $sheets
             . '</Types>';
    }

    private function buildRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             . '<Relationship Id="rId1"'
             . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"'
             . ' Target="xl/workbook.xml"/>'
             . '</Relationships>';
    }

    private function buildWorkbook(): string
    {
        $xml = '';
        foreach ($this->sheets as $i => $s) {
            $name = $this->xmlEsc($s['name']);
            $rId  = $i + 1;
            $xml .= "<sheet name=\"{$name}\" sheetId=\"{$rId}\" r:id=\"rId{$rId}\"/>";
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
             . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
             . '<sheets>' . $xml . '</sheets>'
             . '</workbook>';
    }

    private function buildWorkbookRels(): string
    {
        $rels = '';
        foreach (array_keys($this->sheets) as $i) {
            $rId  = $i + 1;
            $rels .= '<Relationship Id="rId' . $rId . '"'
                   . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                   . ' Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $n     = count($this->sheets) + 1;
        $rels .= '<Relationship Id="rId' . $n . '"'
               . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings"'
               . ' Target="sharedStrings.xml"/>';
        $n++;
        $rels .= '<Relationship Id="rId' . $n . '"'
               . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
               . ' Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             . $rels
             . '</Relationships>';
    }

    private function buildStyles(): string
    {
        $fonts = '<fonts count="3">'
               . '<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
               . '<font><b/><sz val="13"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
               . '<font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
               . '</fonts>';

        $fills = '<fills count="11">'
               . '<fill><patternFill patternType="none"/></fill>'
               . '<fill><patternFill patternType="gray125"/></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FF70AD47"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9D9D9"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFBDD7EE"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FF9DC3E6"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFC6EFCE"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFFCE4D6"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEB9C"/></patternFill></fill>'
               . '</fills>';

        $borders = '<borders count="2">'
                 . '<border><left/><right/><top/><bottom/></border>'
                 . '<border>'
                 . '<left style="thin"><color rgb="FFB0B0B0"/></left>'
                 . '<right style="thin"><color rgb="FFB0B0B0"/></right>'
                 . '<top style="thin"><color rgb="FFB0B0B0"/></top>'
                 . '<bottom style="thin"><color rgb="FFB0B0B0"/></bottom>'
                 . '</border>'
                 . '</borders>';

        $masterXfs = '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';

        // Indices 0-14 deben coincidir exactamente con las constantes S_*
        $a  = static fn(string $h = '', string $v = '', bool $w = false, string $i = ''): string =>
            '<alignment'
            . ($h ? " horizontal=\"$h\"" : '')
            . ($v ? " vertical=\"$v\""   : '')
            . ($w ? ' wrapText="1"'      : '')
            . ($i ? " indent=\"$i\""     : '')
            . '/>';

        $xf = static fn(int $font, int $fill, int $border, string $aln = ''): string =>
            '<xf xfId="0" numFmtId="0"'
            . " fontId=\"$font\" fillId=\"$fill\" borderId=\"$border\""
            . ($font   ? ' applyFont="1"'      : '')
            . ($fill   > 1 ? ' applyFill="1"'  : '')
            . ($border ? ' applyBorder="1"'    : '')
            . ($aln    ? ' applyAlignment="1"' : '')
            . ($aln    ? '>' . $aln . '</xf>'  : '/>');

        $xfs = '<cellXfs count="15">'
             . $xf(0, 0, 0)                                                                  // 0 DEFAULT
             . $xf(1, 2, 0, $a('center', 'center'))                                         // 1 HDR_GREEN
             . $xf(2, 3, 0, $a('center', 'center'))                                         // 2 HDR_GREY
             . $xf(2, 4, 0, $a('center', 'center'))                                         // 3 HDR_BLUE
             . $xf(0, 5, 0, $a('', 'center', false, '1'))                                   // 4 HDR_PROG
             . $xf(2, 6, 1, $a('center', 'center'))                                         // 5 COL_NAME
             . $xf(2, 6, 1, $a('center', 'center', true))                                   // 6 COL_DATE
             . $xf(2, 7, 1, $a('center', 'center'))                                         // 7 CELL_A
             . $xf(2, 8, 1, $a('center', 'center'))                                         // 8 CELL_F
             . $xf(0, 9, 1, $a('center', 'center'))                                         // 9 CELL_SESS
             . $xf(0, 0, 1, $a('center', 'center'))                                         // 10 NAME_IDX
             . $xf(0, 0, 1, $a('', 'center', false, '1'))                                   // 11 NAME_TXT
             . $xf(2, 10, 1, $a('center', 'center'))                                        // 12 TOTAL_F
             . $xf(2, 7,  1, $a('center', 'center'))                                        // 13 TOTAL_A
             . $xf(0, 0, 0)                                                                  // 14 BLANK
             . '</cellXfs>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . $fonts . $fills . $borders . $masterXfs . $xfs
             . '</styleSheet>';
    }

    private function buildSharedStrings(): string
    {
        $count = count($this->ssArr);
        $items = '';
        foreach ($this->ssArr as $str) {
            $esc    = $this->xmlEsc($str);
            $items .= '<si><t xml:space="preserve">' . $esc . '</t></si>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
             . ' count="' . $count . '" uniqueCount="' . $count . '">'
             . $items . '</sst>';
    }

    private function buildSheet(int $idx): string
    {
        $sheet      = $this->sheets[$idx];
        $cells      = $sheet['cells'];
        $merges     = $sheet['merges'];
        $colWidths  = $sheet['colWidths'];
        $rowHeights = $sheet['rowHeights'];

        $colsXml = '';
        if (!empty($colWidths)) {
            ksort($colWidths);
            $colsXml = '<cols>';
            foreach ($colWidths as $ci => $w) {
                $n        = $ci + 1;
                $colsXml .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
            }
            $colsXml .= '</cols>';
        }

        $dataXml = '<sheetData>';
        ksort($cells);
        foreach ($cells as $rowNum => $rowCells) {
            $ht      = isset($rowHeights[$rowNum])
                ? ' ht="' . $rowHeights[$rowNum] . '" customHeight="1"' : '';
            $dataXml .= '<row r="' . $rowNum . '"' . $ht . '>';
            ksort($rowCells);
            foreach ($rowCells as $ci => $cell) {
                $ref = $this->cellRef($ci, $rowNum);
                $s   = $cell['s'];
                $t   = $cell['t'];
                $v   = $cell['v'];

                if ($v === null || $v === '') {
                    $dataXml .= '<c r="' . $ref . '" s="' . $s . '"/>';
                } elseif ($t === 's') {
                    $dataXml .= '<c r="' . $ref . '" s="' . $s . '" t="s"><v>' . $v . '</v></c>';
                } else {
                    $dataXml .= '<c r="' . $ref . '" s="' . $s . '"><v>' . $v . '</v></c>';
                }
            }
            $dataXml .= '</row>';
        }
        $dataXml .= '</sheetData>';

        $mergesXml = '';
        if (!empty($merges)) {
            $mergesXml = '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $m) {
                $mergesXml .= '<mergeCell ref="' . $m . '"/>';
            }
            $mergesXml .= '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
             . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
             . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
             . '<sheetFormatPr defaultRowHeight="15" customHeight="0"/>'
             . $colsXml . $dataXml . $mergesXml
             . '</worksheet>';
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Construcción de ZIP en puro PHP (PKZip 2.0, entradas almacenadas sin compresión).
// No requiere ext-zip. Suficiente para XLSX cuyo contenido ya es XML (texto).
// ─────────────────────────────────────────────────────────────────────────────
class XlsxZipBuilder
{
    private array $entries  = [];   // ['name'=>str, 'data'=>str, 'crc'=>int, 'size'=>int]
    private int   $offset   = 0;   // byte offset of each local header

    public function add(string $name, string $data): void
    {
        $crc  = crc32($data);
        $size = strlen($data);
        $this->entries[] = [
            'name'   => $name,
            'data'   => $data,
            'crc'    => $crc,
            'size'   => $size,
            'offset' => $this->offset,
        ];
        // Local file header = 30 bytes + strlen(name) + 0 (no extra) + data
        $this->offset += 30 + strlen($name) + $size;
    }

    public function build(): string
    {
        $localParts = '';
        $centralDir = '';

        foreach ($this->entries as $e) {
            $nameLen = strlen($e['name']);

            // Local file header (signature 0x04034b50)
            $local  = "\x50\x4b\x03\x04";           // signature
            $local .= "\x14\x00";                    // version needed: 2.0
            $local .= "\x00\x00";                    // general purpose flags
            $local .= "\x00\x00";                    // compression: stored
            $local .= "\x00\x00\x00\x00";            // last mod time + date
            $local .= pack('V', $e['crc']);           // CRC-32
            $local .= pack('V', $e['size']);          // compressed size
            $local .= pack('V', $e['size']);          // uncompressed size
            $local .= pack('v', $nameLen);            // filename length
            $local .= "\x00\x00";                    // extra field length
            $local .= $e['name'];
            $local .= $e['data'];
            $localParts .= $local;

            // Central directory entry (signature 0x02014b50)
            $central  = "\x50\x4b\x01\x02";          // signature
            $central .= "\x14\x00";                  // version made by
            $central .= "\x14\x00";                  // version needed
            $central .= "\x00\x00";                  // flags
            $central .= "\x00\x00";                  // compression: stored
            $central .= "\x00\x00\x00\x00";          // mod time + date
            $central .= pack('V', $e['crc']);         // CRC-32
            $central .= pack('V', $e['size']);        // compressed size
            $central .= pack('V', $e['size']);        // uncompressed size
            $central .= pack('v', $nameLen);          // filename length
            $central .= "\x00\x00";                  // extra field length
            $central .= "\x00\x00";                  // file comment length
            $central .= "\x00\x00";                  // disk number start
            $central .= "\x00\x00";                  // internal attributes
            $central .= "\x00\x00\x00\x00";          // external attributes
            $central .= pack('V', $e['offset']);      // offset of local header
            $central .= $e['name'];
            $centralDir .= $central;
        }

        $count  = count($this->entries);
        $cdSize = strlen($centralDir);
        $cdOff  = strlen($localParts);

        // End of central directory record (signature 0x06054b50)
        $eocd  = "\x50\x4b\x05\x06";                 // signature
        $eocd .= "\x00\x00";                         // disk number
        $eocd .= "\x00\x00";                         // disk with central dir
        $eocd .= pack('v', $count);                  // entries on this disk
        $eocd .= pack('v', $count);                  // total entries
        $eocd .= pack('V', $cdSize);                 // size of central directory
        $eocd .= pack('V', $cdOff);                  // offset of central directory
        $eocd .= "\x00\x00";                         // comment length

        return $localParts . $centralDir . $eocd;
    }
}
