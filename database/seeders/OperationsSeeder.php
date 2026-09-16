<?php

namespace Database\Seeders;

use App\Models\Operations;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operations = [
            [
                'code' => 'docx_to_pdf',
                'name' => 'DOCX to PDF Conversion',
                'input_format' => 'docx',
                'output_format' => 'pdf',
                'cc_convert_engine' => 'office',
                'is_active' => true,
            ],
            [
                'code' => 'xlsx_to_pdf',
                'name' => 'Excel to PDF Conversion',
                'input_format' => 'xlsx',
                'output_format' => 'pdf',
                'cc_convert_engine' => 'office',
                'is_active' => true,
            ],
            [
                'code' => 'png_to_pdf',
                'name' => 'PNG to PDF Conversion',
                'input_format' => 'png',
                'output_format' => 'pdf',
                'cc_convert_engine' => 'imagemagick',
                'is_active' => true,
            ],
            [
                'code' => 'pdf_to_txt',
                'name' => 'PDF Text Extraction',
                'input_format' => 'pdf',
                'output_format' => 'txt',
                'cc_convert_engine' => 'pdftotext',
                'is_active' => true,
            ],
        ];

        foreach ($operations as $operation) {
            Operations::firstOrCreate(
                ['code' => $operation['code']],
                $operation
            );
        }
    }
}
