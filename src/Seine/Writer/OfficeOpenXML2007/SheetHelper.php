<?php
/**
 * Copyright (C) 2011 by Martin Vium
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Seine\Writer\OfficeOpenXML2007;

use Seine\Row;
use Seine\Sheet;
use Seine\Style;
use Seine\Parser\DOM\DOMCell;
use Seine\Writer\OfficeOpenXML2007StreamWriter as MyWriter;
use Seine\IOException;

final class SheetHelper
{
    /**
     * @var Sheet
     */
    private $sheet;

    /**
     * @var SharedStringsHelper
     */
    private $sharedStrings;

    /**
     * @var Style
     */
    private $defaultStyle;

    private $filename;
    private $stream;
    private $rowId = 0;

    public function __construct(Sheet $sheet, SharedStringsHelper $sharedStrings, Style $defaultStyle, $filename)
    {
        $this->sheet = $sheet;
        $this->sharedStrings = $sharedStrings;
        $this->defaultStyle = $defaultStyle;
        $this->filename = $filename;
    }

    public function start()
    {
        $this->stream = fopen($this->filename, 'w');
        if(! $this->stream) {
            throw new IOException('failed to open stream: ' . $this->filename);
        }

        fwrite($this->stream, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . MyWriter::EOL);
        fwrite($this->stream, $this->sheet->getCols() . MyWriter::EOL);
        fwrite($this->stream, '    <sheetData>' . MyWriter::EOL);

    }

    public function writeRow(Row $row)
    {
        $columnId = 'A';
        $rowId = ++$this->rowId;
        $out = '        <row>' . MyWriter::EOL;
        foreach($row->getCells() as $cell) {
            $out .= '            <c r="' . $columnId . $rowId . '"';
            $style = $row->getStyle() ?: $this->defaultStyle;
            if(is_numeric($cell)) {
                $out .= ' s="' . ($style->getId()) . '"';
                $out .= '><v>' . $cell . '</v></c>' . MyWriter::EOL;
            } elseif ($cell instanceof DOMCell) {
                $style = $cell->getStyle() ?: $style;
                $type = $cell->getType() ?: 's';
                $out .= ' s="' . $style->getId() . '"';
                $out .= ' t="'.$type.'"><v>' . $cell->getValue() . '</v></c>' . MyWriter::EOL;
            } else {
                $out .= ' s="' . ($style->getId()) . '"';
                $sharedStringId = $this->sharedStrings->writeString($cell);
                $out .= ' t="s"><v>' . $sharedStringId . '</v></c>' . MyWriter::EOL;
            }
            $columnId++;
        }

        fwrite($this->stream, $out . '        </row>' . MyWriter::EOL);
    }

    public function end()
    {
        fwrite($this->stream, '    </sheetData>' . MyWriter::EOL);
        fwrite($this->stream, '</worksheet>');
        fclose($this->stream);
    }
}