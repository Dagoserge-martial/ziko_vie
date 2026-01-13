<?php

namespace App\Http\Controllers;

use App\Models\ExpenseAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseAttachmentController extends Controller
{
    /**
     * Download the attachment file.
     */
    public function download(ExpenseAttachment $expenseAttachment)
    {
        if (!Storage::disk('public')->exists($expenseAttachment->chemin_fichier)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $expenseAttachment->chemin_fichier,
            $expenseAttachment->nom_fichier
        );
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(ExpenseAttachment $expenseAttachment)
    {
        Storage::disk('public')->delete($expenseAttachment->chemin_fichier);
        $expenseAttachment->delete();

        return back()->with('success', 'Pièce jointe supprimée avec succès.');
    }
}
