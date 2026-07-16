// =============================================================
// AttendQR — UTIL_XlsxExport
// =============================================================
// Generación de reportes .xlsx para Google Apps Script.
// Replica la API de Src/Utils/XlsxWriter.php
//
// En PHP, XlsxWriter construye el ZIP/OOXML binario en memoria pura.
// En GAS no se puede generar ZIP con rutas de directorio, por lo que
// se crea un Google Spreadsheet temporal, se rellena con datos y
// formato, y se exporta como .xlsx via DriveApp.getAs().
// El método output() retorna el binario como base64.
//
// Uso (idéntico al PHP salvo que XlsxExport.crear() reemplaza new XlsxWriter()):
//   var xlsx = XlsxExport.crear();
//   var sheetIdx = xlsx.addSheet('Ficha 1234');
//   xlsx.cell(sheetIdx, 'B', 2, 'INSTRUCTOR: Juan', XlsxExport.S_HDR_GREEN);
//   xlsx.merge(sheetIdx, 'B2', 'H2');
//   xlsx.colWidth(sheetIdx, 'A', 1.5);
//   var base64 = xlsx.output();   // → base64 del binario .xlsx
//
// Constantes de estilo (mismos valores enteros que XlsxWriter::S_*):
//   S_DEFAULT=0  S_HDR_GREEN=1  S_HDR_GREY=2   S_HDR_BLUE=3
//   S_HDR_PROG=4 S_COL_NAME=5  S_COL_DATE=6   S_CELL_A=7
//   S_CELL_F=8   S_CELL_SESS=9 S_NAME_IDX=10  S_NAME_TXT=11
//   S_TOTAL_F=12 S_TOTAL_A=13  S_BLANK=14
// =============================================================

var XlsxExport = (function () {

  // ── Constantes de estilo ─────────────────────────────────────
  var S_DEFAULT   = 0;
  var S_HDR_GREEN = 1;
  var S_HDR_GREY  = 2;
  var S_HDR_BLUE  = 3;
  var S_HDR_PROG  = 4;
  var S_COL_NAME  = 5;
  var S_COL_DATE  = 6;
  var S_CELL_A    = 7;
  var S_CELL_F    = 8;
  var S_CELL_SESS = 9;
  var S_NAME_IDX  = 10;
  var S_NAME_TXT  = 11;
  var S_TOTAL_F   = 12;
  var S_TOTAL_A   = 13;
  var S_BLANK     = 14;

  // ── Definición de estilos → SpreadsheetApp format ────────────
  // bg: color de fondo hex | fontColor: color de texto | bold: negrita
  // size: tamaño de fuente | hAlign: alineación horizontal
  // vAlign: alineación vertical | wrap: ajuste de texto | borders: borde gris fino
  var STYLE_DEFS = {
    0:  {},
    1:  { bg: '#70AD47', fontColor: '#FFFFFF', bold: true, size: 13, hAlign: 'center', vAlign: 'middle' },
    2:  { bg: '#D9D9D9', bold: true,           hAlign: 'center', vAlign: 'middle' },
    3:  { bg: '#BDD7EE', bold: true,           hAlign: 'center', vAlign: 'middle' },
    4:  { bg: '#F2F2F2',                       vAlign: 'middle' },
    5:  { bg: '#9DC3E6', bold: true,           hAlign: 'center', vAlign: 'middle', borders: true },
    6:  { bg: '#9DC3E6', bold: true,           hAlign: 'center', vAlign: 'middle', wrap: true, borders: true },
    7:  { bg: '#C6EFCE', bold: true,           hAlign: 'center', vAlign: 'middle', borders: true },
    8:  { bg: '#FCE4D6', bold: true,           hAlign: 'center', vAlign: 'middle', borders: true },
    9:  { bg: '#FFFFFF',                       hAlign: 'center', vAlign: 'middle', borders: true },
    10: {                                      hAlign: 'center', vAlign: 'middle', borders: true },
    11: {                                      vAlign: 'middle', borders: true },
    12: { bg: '#FFEB9C', bold: true,           hAlign: 'center', vAlign: 'middle', borders: true },
    13: { bg: '#C6EFCE', bold: true,           hAlign: 'center', vAlign: 'middle', borders: true },
    14: {}
  };

  // ── Letra(s) de columna → índice base 0 ──────────────────────
  function _colIndex(col) {
    col = String(col).toUpperCase().trim();
    var n = 0;
    for (var i = 0; i < col.length; i++) {
      n = n * 26 + (col.charCodeAt(i) - 64);
    }
    return n - 1;
  }

  // ── Índice base 0 → letra(s) de columna ──────────────────────
  function _colLetter(idx) {
    var n = idx + 1;
    var l = '';
    while (n > 0) {
      n--;
      l = String.fromCharCode(65 + (n % 26)) + l;
      n = Math.floor(n / 26);
    }
    return l;
  }

  // ── Aplicar definición de estilo a un Range de SpreadsheetApp ─
  function _applyStyle(range, styleId) {
    var def = STYLE_DEFS[styleId];
    if (!def) return;
    if (def.bg)        range.setBackground(def.bg);
    if (def.fontColor) range.setFontColor(def.fontColor);
    if (def.bold)      range.setFontWeight('bold');
    if (def.size)      range.setFontSize(def.size);
    if (def.hAlign)    range.setHorizontalAlignment(def.hAlign);
    if (def.vAlign)    range.setVerticalAlignment(def.vAlign);
    if (def.wrap)      range.setWrap(true);
    if (def.borders) {
      range.setBorder(true, true, true, true, false, false,
                      '#B0B0B0', SpreadsheetApp.BorderStyle.SOLID);
    }
  }

  // ── Poblar una hoja GAS con datos + formato ───────────────────
  function _populate(gs, sheetData) {
    var cells   = sheetData.cells;
    var rowKeys = Object.keys(cells).map(Number);
    if (rowKeys.length === 0) return;

    rowKeys.sort(function (a, b) { return a - b; });
    var maxRow = rowKeys[rowKeys.length - 1];
    var maxCol = 0;

    rowKeys.forEach(function (r) {
      Object.keys(cells[r]).map(Number).forEach(function (c) {
        if (c > maxCol) maxCol = c;
      });
    });

    // Construir grilla de valores 2D para setValues en un solo llamado
    var grid = [];
    for (var r = 1; r <= maxRow; r++) {
      var rowArr = [];
      for (var c = 0; c <= maxCol; c++) {
        var cell = cells[r] && cells[r][c];
        var val  = cell ? cell.v : '';
        rowArr.push(val === null || val === undefined ? '' : val);
      }
      grid.push(rowArr);
    }
    if (maxRow > 0 && maxCol >= 0) {
      gs.getRange(1, 1, maxRow, maxCol + 1).setValues(grid);
    }

    // Aplicar estilos celda por celda (las celdas sin estilo se omiten)
    rowKeys.forEach(function (r) {
      Object.keys(cells[r]).map(Number).forEach(function (c) {
        var cell = cells[r][c];
        if (cell && cell.s !== 0) {
          _applyStyle(gs.getRange(r, c + 1), cell.s);
        }
      });
    });

    // Aplicar combinaciones de celdas (merges)
    sheetData.merges.forEach(function (m) {
      try { gs.getRange(m).merge(); } catch (e) { /* rango inválido — ignorar */ }
    });

    // Aplicar anchos de columna (unidades xlsx char ≈ 8 px)
    Object.keys(sheetData.colWidths).forEach(function (ci) {
      var px = Math.max(20, Math.round(sheetData.colWidths[ci] * 8));
      gs.setColumnWidth(parseInt(ci, 10) + 1, px);
    });

    // Aplicar alturas de fila (puntos ≈ 1.33 px)
    Object.keys(sheetData.rowHeights).forEach(function (ri) {
      var px = Math.max(15, Math.round(sheetData.rowHeights[ri] * 1.33));
      gs.setRowHeight(parseInt(ri, 10), px);
    });
  }

  // ── Fábrica de instancias ─────────────────────────────────────
  // Reemplaza: new XlsxWriter()
  function crear() {
    var _sheets = [];

    function addSheet(name) {
      var idx = _sheets.length;
      _sheets.push({ name: String(name), cells: {}, merges: [], colWidths: {}, rowHeights: {} });
      return idx;
    }

    function cell(sheetIdx, colStr, row, value, style) {
      var col = _colIndex(colStr);
      if (!_sheets[sheetIdx].cells[row]) _sheets[sheetIdx].cells[row] = {};
      _sheets[sheetIdx].cells[row][col] = { v: value, s: (style === undefined || style === null) ? 0 : style };
    }

    function merge(sheetIdx, from, to) {
      _sheets[sheetIdx].merges.push(from + ':' + to);
    }

    function colWidth(sheetIdx, colStr, width) {
      _sheets[sheetIdx].colWidths[_colIndex(colStr)] = width;
    }

    function rowHeight(sheetIdx, row, height) {
      _sheets[sheetIdx].rowHeights[row] = height;
    }

    function colLetter(idx) {
      return _colLetter(idx);
    }

    // Genera el .xlsx y retorna su contenido como cadena base64.
    // Crea un Spreadsheet temporal en Drive, lo popula, lo exporta y lo elimina.
    function output() {
      if (_sheets.length === 0) throw new Error('Sin hojas para exportar.');

      var tmpName = 'AttendQR_Export_' + new Date().getTime();
      var ss      = SpreadsheetApp.create(tmpName);
      var gsArr   = [];

      // Primera hoja ya existe; insertar las adicionales
      for (var i = 0; i < _sheets.length; i++) {
        var gs;
        if (i === 0) {
          gs = ss.getSheets()[0];
          gs.setName(_sheets[0].name);
        } else {
          gs = ss.insertSheet(_sheets[i].name);
        }
        gsArr.push(gs);
      }

      // Poblar cada hoja con datos y formato
      for (var j = 0; j < _sheets.length; j++) {
        _populate(gsArr[j], _sheets[j]);
      }

      // Exportar como xlsx via Drive export API (DriveApp.getAs falla en google.script.run)
      var fileId   = ss.getId();
      var exportUrl = 'https://docs.google.com/spreadsheets/d/' + fileId + '/export?format=xlsx';
      var response  = UrlFetchApp.fetch(exportUrl, {
        headers: { Authorization: 'Bearer ' + ScriptApp.getOAuthToken() },
        muteHttpExceptions: true
      });
      if (response.getResponseCode() !== 200) {
        throw new Error('Error al exportar xlsx: HTTP ' + response.getResponseCode());
      }
      var base64 = Utilities.base64Encode(response.getContent());

      // Limpiar Spreadsheet temporal
      DriveApp.getFileById(fileId).setTrashed(true);

      return base64;
    }

    return {
      addSheet  : addSheet,
      cell      : cell,
      merge     : merge,
      colWidth  : colWidth,
      rowHeight : rowHeight,
      colLetter : colLetter,
      output    : output
    };
  }

  // ── API pública del módulo ────────────────────────────────────
  return {
    // Constantes de estilo (accesibles como XlsxExport.S_HDR_GREEN, etc.)
    S_DEFAULT   : S_DEFAULT,
    S_HDR_GREEN : S_HDR_GREEN,
    S_HDR_GREY  : S_HDR_GREY,
    S_HDR_BLUE  : S_HDR_BLUE,
    S_HDR_PROG  : S_HDR_PROG,
    S_COL_NAME  : S_COL_NAME,
    S_COL_DATE  : S_COL_DATE,
    S_CELL_A    : S_CELL_A,
    S_CELL_F    : S_CELL_F,
    S_CELL_SESS : S_CELL_SESS,
    S_NAME_IDX  : S_NAME_IDX,
    S_NAME_TXT  : S_NAME_TXT,
    S_TOTAL_F   : S_TOTAL_F,
    S_TOTAL_A   : S_TOTAL_A,
    S_BLANK     : S_BLANK,
    // Fábrica
    crear       : crear
  };

})();
