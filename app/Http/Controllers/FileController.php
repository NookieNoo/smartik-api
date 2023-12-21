<?php

namespace App\Http\Controllers;

use App\Models\IntegrationReport;
use BeyondCode\Mailbox\InboundEmail;

class FileController
{
    public function mail ($mail)
    {
        $mail = InboundEmail::find($mail);
        foreach ($mail->attachments() as $attachment) {
            $attachment->saveContent(storage_path('app/temp/' . $attachment->getFilename()));
            return response()->file(storage_path('app/temp/' . $attachment->getFilename()));
        }
    }

    public function report ($report)
    {
        $report = IntegrationReport::find($report);
        return response()->download($report->file);
    }
}