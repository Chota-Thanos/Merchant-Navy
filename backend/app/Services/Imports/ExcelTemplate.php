<?php

namespace App\Services\Imports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/** The blank spreadsheet admins fill in (columns are read by ExcelParser). */
class ExcelTemplate
{
    public const HEADER = ['No', 'Question', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct (A-D)', 'Explanation', 'Difficulty (easy/medium/hard)', 'Part'];

    /** Writes the template and returns its path (a temporary file). */
    public function build(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(self::HEADER));
        $writer->addRow(Row::fromValues([1, 'The SI unit of surface tension is:', 'N/m²', 'N/m', 'J/m', 'kg m/s', 'B', 'Surface tension is force per unit length, so the unit is N/m.', 'easy', 'Physics']));
        $writer->addRow(Row::fromValues([2, 'The value of $\\int_0^1 x\\,dx$ is:', '$1$', '$\\tfrac{1}{2}$', '$0$', '$2$', 'B', 'The integral of $x$ is $\\tfrac{x^2}{2}$, which is $\\tfrac12$ between 0 and 1.', 'medium', 'Maths']));
        $writer->close();

        return $path;
    }
}
