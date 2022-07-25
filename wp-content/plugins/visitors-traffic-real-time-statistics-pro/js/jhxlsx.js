/*
 * ####################################################################################################
 * https://www.npmjs.com/package/xlsx-style
 * ####################################################################################################
 */
var Jhxlsx = {
    config: {
        fileName: "jhreport",
        extension: ".xlsx",
        sheetName: "Sheet",
        fileFullName: "report.xlsx",
        header: true,
        maxCellWidth: 20
    },
    worksheetObj: {},
    rowCount: 0,
    wsColswidth: [],
    merges: [],
    worksheet: {},
    range: {s: {c: 10000000, r: 10000000}, e: {c: 0, r: 0}},
    init: function (options) {
        this.reset();
        for (var key in this.config) {
            if (options.hasOwnProperty(key)) {
                this.config[key] = options[key];
            }
        }
        this.config['fileFullName'] = this.config.fileName + this.config.extension;
    },
    reset: function () {
        this.worksheetObj = {};
        this.rowCount = 0;
        this.wsColswidth = [];
        this.merges = [];
        this.worksheet = {};
    },
    cellWidth: function (cellText, pos) {
        var max = (cellText.length * 1.3);
        if (this.wsColswidth[pos]) {
            if (max > this.wsColswidth[pos].wch) {
                this.wsColswidth[pos] = {wch: max};
            }
        } else {
            this.wsColswidth[pos] = {wch: max};
        }
    },
    cellWidthValidate: function () {
        for (var i in this.wsColswidth) {
            if (this.wsColswidth[i].wch > this.config.maxCellWidth) {
                this.wsColswidth[i].wch = this.config.maxCellWidth;
            }
        }
    },
    datenum: function (v, date1904) {
        if (date1904)
            v += 1462;
        var epoch = Date.parse(v);
        return (epoch - new Date(Date.UTC(1899, 11, 30))) / (24 * 60 * 60 * 1000);
    },
    setCellDataType: function (cell) {
        if (typeof cell.v === 'number') {
            cell.t = 'n';
        } else if (typeof cell.v === 'boolean') {
            cell.t = 'b';
        } else if (cell.v instanceof Date) {
            cell.t = 'n';
            cell.z = XLSX.SSF._table[14];
            cell.v = this.datenum(cell.v);
        } else {
            cell.t = 's';
        }
    },
    jhAddRow: function (rowObj) {

        for (var c in rowObj) {
            var cellObj = rowObj[c];
            if (this.range.s.r > this.rowCount)
                this.range.s.r = this.rowCount;
            if (this.range.s.c > c)
                this.range.s.c = c;
            if (this.range.e.r < this.rowCount)
                this.range.e.r = this.rowCount;
            if (this.range.e.c < c)
                this.range.e.c = c;

            var cellText = null;
            if (cellObj.hasOwnProperty('text')) {
                cellText = cellObj.text;
            }
            var cell = {v: cellText};

            var calColWidth = true;
            if (cellObj.hasOwnProperty('merge')) {
                calColWidth = false;
                var colStartEnd = cellObj.merge.split('-');
                this.merges.push({s: {r: this.rowCount, c: parseInt(colStartEnd[0])}, e: {r: this.rowCount, c: parseInt(colStartEnd[1])}});
            }
            if (calColWidth) {
                this.cellWidth(cell.v, c);
            }
            if (cell.v === null)
                continue;
            var cell_ref = XLSX.utils.encode_cell({c: c, r: this.rowCount});
            this.setCellDataType(cell);
            /*if (typeof cell.v === 'number') {
             cell.t = 'n';
             } else if (typeof cell.v === 'boolean') {
             cell.t = 'b';
             } else if (cell.v instanceof Date) {
             cell.t = 'n';
             cell.z = XLSX.SSF._table[14];
             cell.v = this.datenum(cell.v);
             } else {
             cell.t = 's';
             }*/
            if (cellObj.hasOwnProperty('style')) {
                cell.s = cellObj.style;
            }

            this.worksheet[cell_ref] = cell;
        }
        this.rowCount++;
    },
    createWorkSheet: function () {
        for (var i in this.worksheetObj.data) {
            this.jhAddRow(this.worksheetObj.data[i]);
        }

        this.cellWidthValidate();
        this.worksheet['!merges'] = this.merges;
        this.worksheet['!cols'] = this.wsColswidth;
        if (this.range.s.c < 10000000)
            this.worksheet['!ref'] = XLSX.utils.encode_range(this.range);
        return this.worksheet;
    },
    s2ab: function (s) {
        var buf = new ArrayBuffer(s.length);
        var view = new Uint8Array(buf);
        for (var i = 0; i != s.length; ++i)
            view[i] = s.charCodeAt(i) & 0xFF;
        return buf;
    },
    export: function (workbookObj, options) {
        this.init(options);
        var workbook = new Workbook();
        /* add worksheet to workbook */
        for (var i in workbookObj) {
            this.reset();
            this.worksheetObj = workbookObj[i];
            var sheetName = this.config.sheetName + i;
            if (this.worksheetObj.hasOwnProperty('sheetName')) {
                sheetName = this.worksheetObj.sheetName;
            }
            this.createWorkSheet();
            workbook.SheetNames.push(sheetName);
            workbook.Sheets[sheetName] = this.worksheet;
        }
        var wbout = XLSX.write(workbook, {bookType: 'xlsx', bookSST: true, type: 'binary'});
        saveAs(new Blob([this.s2ab(wbout)], {type: "application/octet-stream"}), this.config.fileFullName)
    }
}

function Workbook() {
    if (!(this instanceof Workbook))
        return new Workbook();
    this.SheetNames = [];
    this.Sheets = {};
}
