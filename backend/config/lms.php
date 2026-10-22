<?php

/*
| Platform rules in one place.
*/

return [
    // Devices a student can be logged in on at once. A new login signs out the oldest device.
    'max_devices' => (int) env('LMS_MAX_DEVICES', 2),

    // Student app URL (used in emails and CORS).
    'student_app_url' => env('STUDENT_APP_URL', 'http://localhost:3000'),

    // Host of the admin panel (empty locally, where it lives at /admin).
    'admin_domain' => env('ADMIN_DOMAIN'),

    // Country code added to 10-digit phone numbers when sending SMS.
    'default_country_code' => '+91',

    // PyMuPDF helper (tools/pdf_tool.py): reads questions out of PDFs and, if pdftoppm is missing, renders pages.
    //   Setup:  pip install pymupdf
    'python' => env('LMS_PYTHON', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
    'pdf_tool' => base_path('tools/pdf_tool.py'),

    // Protected PDFs: poppler's pdftoppm renders pages to images; a TrueType font is used for the watermark.
    'pdftoppm' => env('LMS_PDFTOPPM', 'pdftoppm'),
    'watermark_font' => env('LMS_WATERMARK_FONT'),

    // How long a page-image link stays valid (minutes). Links are signed and tied to one student.
    'pdf_link_minutes' => 15,

    // Assignment upload limit (KB).
    'assignment_max_kb' => 20480,
];
