(function (global) {
    'use strict';

    const SCHEMA_VERSION = '1.0';
    const MAX_FILE_BYTES = 25 * 1024 * 1024;

    function rowsFor(workbook, sheetName, limit = 30) {
        const sheet = workbook?.Sheets?.[sheetName];
        if (!sheet || !global.XLSX) return [];
        return global.XLSX.utils.sheet_to_json(sheet, { header: 1, blankrows: false }).slice(0, limit);
    }

    function inspect(workbook, options = {}) {
        const kind = options.kind || 'workbook';
        const sheetNames = Array.isArray(workbook?.SheetNames) ? workbook.SheetNames : [];
        const warnings = [];
        const errors = [];
        let detectedVersion = null;

        if (sheetNames.length === 0) {
            errors.push('The workbook has no readable worksheets.');
        }

        const metadata = workbook?.Sheets?.['_COTSWOLD_META'];
        if (metadata?.A1?.v === 'COTSWOLD_WORKBOOK_SCHEMA') {
            detectedVersion = String(metadata?.A2?.v || '');
            if (detectedVersion !== SCHEMA_VERSION) {
                warnings.push(`Workbook schema ${detectedVersion || 'unknown'} differs from supported schema ${SCHEMA_VERSION}.`);
            }
        } else {
            warnings.push('Legacy workbook: no embedded Cotswold schema version was found. Structural checks were used instead.');
        }

        if (kind === 'results' && sheetNames.length) {
            const candidates = sheetNames.filter(name => rowsFor(workbook, name).some(row =>
                Array.isArray(row) && row.filter(cell => String(cell ?? '').trim().toLowerCase().startsWith('lane')).length >= 2
            ));
            if (candidates.length === 0) {
                warnings.push('No worksheet with at least two Lane headings was detected.');
            }
        }

        if (kind === 'teamsheet' && sheetNames.length) {
            const candidates = sheetNames.filter(name => rowsFor(workbook, name).some(row =>
                Array.isArray(row) && row.some(cell => /^event\s*(no\.?|number)?$/i.test(String(cell ?? '').trim()))
            ));
            if (candidates.length === 0) {
                warnings.push('No standard Event Number heading was detected; the selected sheet will be checked row by row.');
            }
        }

        return {
            compatible: errors.length === 0,
            schemaVersion: detectedVersion,
            supportedSchemaVersion: SCHEMA_VERSION,
            sheets: sheetNames.slice(),
            warnings,
            errors,
        };
    }

    async function checksum(arrayBuffer) {
        if (!global.crypto?.subtle || !(arrayBuffer instanceof ArrayBuffer)) return null;
        const digest = await global.crypto.subtle.digest('SHA-256', arrayBuffer);
        return Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('');
    }

    async function validate(workbook, options = {}) {
        if (options.file && options.file.size > MAX_FILE_BYTES) {
            throw new Error('Workbook is larger than the 25 MB safety limit.');
        }
        const report = inspect(workbook, options);
        report.filename = options.file?.name || options.filename || '';
        report.sha256 = options.arrayBuffer ? await checksum(options.arrayBuffer) : null;
        if (!report.compatible) {
            throw new Error(report.errors.join(' '));
        }
        if (report.warnings.length) {
            console.warn('Cotswold workbook compatibility report', report);
        }
        global.dispatchEvent(new CustomEvent('cotswold:workbook-validated', { detail: report }));
        return report;
    }

    global.CotswoldWorkbook = { SCHEMA_VERSION, inspect, validate };
})(window);
